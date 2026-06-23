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

    public function getComponent(xmlDom $dom) {
        return new ChartComponent($dom, $this->series, $this->meta);
    }

    public function render(xmlDom $dom, $parent = null) {
        $component = $this->getComponent($dom);
        $element = $component->render();
        if ($parent) {
            $parent->appendChild($element);
        }
        return $element;
    }
}
