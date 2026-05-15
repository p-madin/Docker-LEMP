<?php

class PlotMeta {
    public $xMin, $xMax, $yMin, $yMax;
    public $xDistance, $yDistance;

    public function __construct() {
        $this->xMin = INF;
        $this->xMax = -INF;
        $this->yMin = INF;
        $this->yMax = -INF;
    }

    public function setRange() {
        $this->xDistance = ($this->xMax - $this->xMin) ?: 1;
        $this->yDistance = ($this->yMax - $this->yMin) ?: 1;
    }
}

class PlotItem {
    public $x, $y;
    public $xStructured, $xPrimitive, $yStructured;
    public $xRel, $xRelPercSep, $xRelPerc;
    public $yRel, $yRelPercSep, $yRelPerc;

    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
        $this->xStructured = new DateTime($this->x);
        $this->xPrimitive = (float)($this->xStructured->format('Uv'));
        $this->yStructured = (float)$this->y;
    }
}

class DataGraphComponent extends Component {
    private array $series;
    private PlotMeta $meta;

    private int $graphXDistance = 425;
    private int $graphYDistance = 175;
    private int $plotXCanvasRatio = 80;
    private int $plotXCanvasOffset = 10;
    private int $plotYCanvasRatio = 85;
    private int $plotYCanvasOffset = -90;

    private ?string $dataSource = null;
    private array $dataConfig = [];

    public function __construct(xmlDom $xmlDom, array $series = [], ?PlotMeta $meta = null) {
        parent::__construct($xmlDom, 'div', ['class' => 'graph-container']);
        $this->series = $series;
        $this->meta = $meta ?? new PlotMeta();
    }

    public function setDataSource(?string $source) {
        $this->dataSource = $source;
        return $this;
    }

    public function setDataConfig(array $config) {
        $this->dataConfig = $config;
        return $this;
    }

    protected function build(): void {
        if ($this->dataSource) {
            global $db, $dialect;
            $qb = new QueryBuilder($dialect);
            
            $rawData = $qb->table($this->dataSource)->getFetchAll($db);

            $mapper = new \Services\GenericDataMapper();
            $mappedData = $mapper->map($rawData, $this->dataConfig['mapping'] ?? []);

            // Repopulate series and meta
            $this->series = [];
            $this->meta = new PlotMeta();
            foreach ($mappedData as $item) {
                // Expecting 'x' and 'y' after mapping
                $plot = new PlotItem($item['x'] ?? '', $item['y'] ?? 0);
                $this->series[] = $plot;

                if ($plot->xPrimitive > $this->meta->xMax) $this->meta->xMax = $plot->xPrimitive;
                if ($plot->xPrimitive < $this->meta->xMin) $this->meta->xMin = $plot->xPrimitive;
                if ($plot->yStructured > $this->meta->yMax) $this->meta->yMax = $plot->yStructured;
                if ($plot->yStructured < $this->meta->yMin) $this->meta->yMin = $plot->yStructured;
            }

            usort($this->series, function($a, $b) {
                return $a->xPrimitive <=> $b->xPrimitive;
            });

            $this->meta->setRange();
        }

        $pathData = "";
        
        // Root SVG
        $rootSvg = $this->fabricateChild($this->root, "svg", [
            "xmlns" => "http://www.w3.org/2000/svg",
            "id" => "plot",
            "width" => "100%",
            "height" => "100%"
        ]);

        // Internal container
        $internalHub = $this->fabricateChild($rootSvg, "svg", [
            "viewBox" => "-43 0 425 200",
            "preserveAspectRatio" => "none"
        ]);

        // Plot Area
        $plotArea = $this->fabricateChild($internalHub, "svg", [
            "id" => "plot-area",
            "viewBox" => "0 0 530 205",
            "preserveAspectRatio" => "none"
        ]);

        foreach ($this->series as $plot) {
            $plot->xRel = $plot->xPrimitive - $this->meta->xMin;
            $plot->xRelPerc = ($plot->xRel / $this->meta->xDistance) * $this->graphXDistance;
            $plot->xRelPercSep = ($plot->xRel / $this->meta->xDistance) * $this->plotXCanvasRatio + $this->plotXCanvasOffset;

            $plot->yRel = $plot->yStructured - $this->meta->yMin;
            $plot->yRelPerc = ($plot->yRel / $this->meta->yDistance) * $this->graphYDistance * -1 + $this->graphYDistance;
            $plot->yRelPercSep = ($plot->yRel / $this->meta->yDistance) * $this->plotYCanvasRatio * -1 + $this->graphYDistance + $this->plotYCanvasOffset;

            if ($pathData === "") {
                $pathData = "M ".$plot->xRelPerc." ".$plot->yRelPerc;
            } else {
                $pathData .= " L ".$plot->xRelPerc." ".$plot->yRelPerc;
            }

            // Dots
            $dotWrapper = $this->fabricateChild($rootSvg, "svg", ["class" => "dot"]);
            $this->fabricateChild($dotWrapper, "circle", [
                "cx" => round($plot->xRelPercSep + 0.3, 2) . '%',
                "cy" => round($plot->yRelPercSep, 2) . '%',
                "r" => "10px"
            ]);

            // Collision / Tooltip
            $xBuf = ($plot->xRel / $this->meta->xDistance);
            if ($xBuf > 0.7) $xBuf -= 0.37;
            $xBuf = $xBuf * $this->plotXCanvasRatio + $this->plotXCanvasOffset;

            $yBuf = ($plot->yRel / $this->meta->yDistance);
            if ($yBuf < 0.2) $yBuf += 0.10;
            $yBuf = ($yBuf * $this->plotYCanvasRatio * -1 + $this->graphYDistance) + $this->plotYCanvasOffset;

            $collisionWrapper = $this->fabricateChild($rootSvg, "svg", ["class" => "collision"]);
            $fo = $this->fabricateChild($collisionWrapper, "foreignObject", [
                "width" => "25%",
                "x" => round($xBuf, 2) . '%',
                "y" => round($yBuf, 2) . '%',
                "style" => "overflow:visible;"
            ]);
            $window = $this->fabricateChild($fo, "div", ["class" => "window"]);
            
            $divDate = $this->fabricateChild($window, "div");
            $this->fabricateChild($divDate, "b", [], "Date: ");
            $this->fabricateChild($divDate, "span", [], $plot->xStructured->format('d/m/Y H:i'));

            $divValue = $this->fabricateChild($window, "div");
            $this->fabricateChild($divValue, "b", [], "Value: ");
            $this->fabricateChild($divValue, "span", [], (string)$plot->y);

            $this->fabricateChild($collisionWrapper, "rect", [
                "width" => "4%",
                "height" => "5%",
                "x" => round($plot->xRelPercSep - 2, 2) . '%',
                "y" => round($plot->yRelPercSep - 2, 2) . '%'
            ]);
        }

        // Path
        $this->fabricateChild($plotArea, "path", [
            "id" => "doc_path",
            "d" => $pathData,
            "stroke" => "black",
            "fill" => "transparent",
            "style" => "vector-effect:non-scaling-stroke"
        ]);

        $this->appendAxes($plotArea, $rootSvg);
    }

    private function appendAxes(\DOM\Element $plotArea, \DOM\Element $rootSvg) {
        $yStep = $this->meta->yDistance / 4;
        $yStepP = $this->graphYDistance / 4;
        
        $yAxisLabelWrapper = $this->fabricateChild($rootSvg, "svg");
        for ($i = 0; $i < 5; $i++) {
            $val = $this->meta->yMin + ($i * $yStep);
            $y = ($i * $yStepP * -1) + $this->graphYDistance;
            
            // Grid
            $this->fabricateChild($plotArea, "line", [
                "class" => "axis_grid",
                "x1" => "0",
                "x2" => (string)$this->graphXDistance,
                "y1" => (string)round($y, 2),
                "y2" => (string)round($y, 2)
            ]);

            // Label
            $yPerc = (81 - ($i * 20));
            $axisWrapper = $this->fabricateChild($yAxisLabelWrapper, "svg", ["class" => "axis"]);
            $fo = $this->fabricateChild($axisWrapper, "foreignObject", [
                "width" => "8%",
                "height" => "40px",
                "x" => "10",
                "y" => $yPerc . "%"
            ]);
            $this->fabricateChild($fo, "div", [], (string)$val);
        }

        $xStep = $this->meta->xDistance / 4;
        $xStepP = $this->graphXDistance / 4;
        
        $xAxisLabelWrapper = $this->fabricateChild($rootSvg, "svg");
        for ($i = 0; $i < 5; $i++) {
            $val = $this->meta->xMin + ($i * $xStep);
            $date = new DateTime("@" . (int)($val / 1000));
            $x = ($i * $xStepP);
            
            // Grid
            $this->fabricateChild($plotArea, "line", [
                "class" => "axis_grid",
                "x1" => (string)round($x, 2),
                "x2" => (string)round($x, 2),
                "y1" => "0",
                "y2" => "175"
            ]);

            // Label
            $xPerc = 6 + ($i * 18);
            $axisWrapperAttributes = ["class"=>"axis"];
            if($i%2 == 1){
                $axisWrapperAttributes = ["class"=>"axis minor"];
            }
            $axisWrapper = $this->fabricateChild($xAxisLabelWrapper, "svg", $axisWrapperAttributes);
            $fo = $this->fabricateChild($axisWrapper, "foreignObject", [
                "width" => "15%",
                "height" => "40px",
                "x" => $xPerc . "%",
                "y" => "87%",
                "style" => "overflow:visible;"
            ]);
            $this->fabricateChild($fo, "div", [], $date->format('d/m/Y H:i'));
        }
    }
}

class DataGraph {
    private $series = [];
    private $meta;

    public function __construct($dataSeries) {
        $this->meta = new PlotMeta();
        foreach ($dataSeries as $item) {
            $plot = new PlotItem($item['x'], $item['y']);
            $this->series[] = $plot;

            if ($plot->xPrimitive > $this->meta->xMax) $this->meta->xMax = $plot->xPrimitive;
            if ($plot->xPrimitive < $this->meta->xMin) $this->meta->xMin = $plot->xPrimitive;
            if ($plot->yStructured > $this->meta->yMax) $this->meta->yMax = $plot->yStructured;
            if ($plot->yStructured < $this->meta->yMin) $this->meta->yMin = $plot->yStructured;
        }

        usort($this->series, function($a, $b) {
            return $a->xPrimitive <=> $b->xPrimitive;
        });

        $this->meta->setRange();
    }

    public function render(xmlDom $dom, $parent = null) {
        $component = new DataGraphComponent($dom, $this->series, $this->meta);
        $element = $component->render();
        if ($parent) {
            $parent->appendChild($element);
        }
        return $element;
    }
}
