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

class DataGraph {
    private $series = [];
    private $meta;

    private $graphXDistance = 425;
    private $graphYDistance = 175;
    private $plotXCanvasRatio = 80;
    private $plotXCanvasOffset = 10;
    private $plotYCanvasRatio = 85;
    private $plotYCanvasOffset = -90;

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

    public function render($dom, $parent) {
        $pathData = "";
        
        // Root SVG
        $rootSvg = $dom->appendChild($parent, "svg", [
            "xmlns" => "http://www.w3.org/2000/svg",
            "id" => "plot",
            "width" => "100%",
            "height" => "100%"
        ]);

        // Internal container
        $internalHub = $dom->appendChild($rootSvg, "svg", [
            "viewBox" => "-43 0 425 200",
            "preserveAspectRatio" => "none"
        ]);

        // Plot Area
        $plotArea = $dom->appendChild($internalHub, "svg", [
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
            $dotWrapper = $dom->appendChild($rootSvg, "svg", ["class" => "dot"]);
            $dom->appendChild($dotWrapper, "circle", [
                "cx" => round($plot->xRelPercSep + 0.3, 2) . '%',
                "cy" => round($plot->yRelPercSep, 2) . '%',
                "r" => "10px"
            ]);

            // Collision
            $xBuf = ($plot->xRel / $this->meta->xDistance);
            if ($xBuf > 0.7) $xBuf -= 0.37;
            $xBuf = $xBuf * $this->plotXCanvasRatio + $this->plotXCanvasOffset;

            $yBuf = ($plot->yRel / $this->meta->yDistance);
            if ($yBuf < 0.2) $yBuf += 0.10;
            $yBuf = ($yBuf * $this->plotYCanvasRatio * -1 + $this->graphYDistance) + $this->plotYCanvasOffset;

            $collisionWrapper = $dom->appendChild($rootSvg, "svg", ["class" => "collision"]);
            $fo = $dom->appendChild($collisionWrapper, "foreignObject", [
                "width" => "25%",
                "x" => round($xBuf, 2) . '%',
                "y" => round($yBuf, 2) . '%',
                "style" => "overflow:visible;"
            ]);
            $window = $dom->appendChild($fo, "div", ["class" => "window"]);
            
            $divDate = $dom->appendChild($window, "div");
            $dom->appendChild($divDate, "b", [], "Date: ");
            $divDate->appendChild($dom->dom->createTextNode($plot->xStructured->format('d/m/Y')));

            $divValue = $dom->appendChild($window, "div");
            $dom->appendChild($divValue, "b", [], "Value: ");
            $divValue->appendChild($dom->dom->createTextNode($plot->y));

            $dom->appendChild($collisionWrapper, "rect", [
                "width" => "4%",
                "height" => "5%",
                "x" => round($plot->xRelPercSep - 2, 2) . '%',
                "y" => round($plot->yRelPercSep - 2, 2) . '%'
            ]);
        }

        // Path
        $dom->appendChild($plotArea, "path", [
            "id" => "doc_path",
            "d" => $pathData,
            "stroke" => "black",
            "fill" => "transparent",
            "style" => "vector-effect:non-scaling-stroke"
        ]);

        $this->appendAxes($dom, $plotArea, $rootSvg);
    }

    private function appendAxes($dom, $plotArea, $rootSvg) {
        $yStep = $this->meta->yDistance / 4;
        $yStepP = $this->graphYDistance / 4;
        
        $yAxisLabelWrapper = $dom->appendChild($rootSvg, "svg");
        for ($i = 0; $i < 5; $i++) {
            $val = $this->meta->yMin + ($i * $yStep);
            $y = ($i * $yStepP * -1) + $this->graphYDistance;
            
            // Grid
            $dom->appendChild($plotArea, "line", [
                "class" => "axis_grid",
                "x1" => "0",
                "x2" => $this->graphXDistance,
                "y1" => round($y, 2),
                "y2" => round($y, 2)
            ]);

            // Label
            $yPerc = (81 - ($i * 20));
            $axisWrapper = $dom->appendChild($yAxisLabelWrapper, "svg", ["class" => "axis"]);
            $fo = $dom->appendChild($axisWrapper, "foreignObject", [
                "width" => "8%",
                "height" => "40px",
                "x" => "10",
                "y" => $yPerc . "%"
            ]);
            $dom->appendChild($fo, "div", [], (string)$val);
        }

        $xStep = $this->meta->xDistance / 4;
        $xStepP = $this->graphXDistance / 4;
        
        $xAxisLabelWrapper = $dom->appendChild($rootSvg, "svg");
        for ($i = 0; $i < 5; $i++) {
            $val = $this->meta->xMin + ($i * $xStep);
            $date = new DateTime("@" . (int)($val / 1000));
            $x = ($i * $xStepP);
            
            // Grid
            $dom->appendChild($plotArea, "line", [
                "class" => "axis_grid",
                "x1" => round($x, 2),
                "x2" => round($x, 2),
                "y1" => "0",
                "y2" => "175"
            ]);

            // Label
            $xPerc = 6 + ($i * 18);
            $axisWrapperAttributes = ["class"=>"axis"];
            if($i%2 == 1){
                $axisWrapperAttributes = ["class"=>"axis minor"];
            }
            $axisWrapper = $dom->appendChild($xAxisLabelWrapper, "svg", $axisWrapperAttributes);
            $fo = $dom->appendChild($axisWrapper, "foreignObject", [
                "width" => "15%",
                "height" => "40px",
                "x" => $xPerc . "%",
                "y" => "87%",
                "style" => "overflow:visible;"
            ]);
            $dom->appendChild($fo, "div", [], $date->format('d/m/Y H:i'));
        }
    }

    public static function fromXML($xmlString) {
        $series = [];
        $xml = new SimpleXMLElement($xmlString);
        foreach ($xml->item as $item) {
            $series[] = [
                'x' => (string)$item->x,
                'y' => (string)$item->y
            ];
        }
        return new self($series);
    }
}
