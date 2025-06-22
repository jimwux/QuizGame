<?php

class GraphController
{
    private $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function usuariosPorPais()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorPais($filtro);
        $this->generarGraficoTorta($datos, "Jugadores por país", 'pais');
    }

    public function usuariosPorSexo()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorSexo($filtro);
        $this->generarGraficoTorta($datos, "Jugadores por sexo", 'sexo');
    }

    public function usuariosPorGrupo()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';
        $datos = $this->model->obtenerUsuariosPorGrupoEtario($filtro);
        $this->generarGraficoBarra($datos, "Jugadores por grupo etario");
    }

    private function generarGraficoTorta($datos, $titulo, $clave)
    {
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_pie.php';

        header("Content-Type: image/png");

        if (empty($datos)) {
            $this->crearGraficoVacio($titulo);
            return;
        }

        $valores = array_column($datos, 'cantidad');
        $labels = array_column($datos, $clave);

        $graph = new PieGraph(400, 300);
        $graph->title->Set($titulo);

        $plot = new PiePlot($valores);
        $plot->SetLegends($labels);
        $graph->Add($plot);
        $graph->Stroke();
    }

    private function generarGraficoBarra($datos, $titulo)
    {
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph.php';
        require_once __DIR__ . '/../libs/jpgraph/src/jpgraph_bar.php';

        header("Content-Type: image/png");

        if (empty($datos)) {
            $this->crearGraficoVacio($titulo);
            return;
        }

        $valores = array_column($datos, 'cantidad');
        $labels = array_column($datos, 'grupo_etario');

        $graph = new Graph(400, 300);
        $graph->SetScale('textint');
        $graph->xaxis->SetTickLabels($labels);
        $graph->title->Set($titulo);

        $barplot = new BarPlot($valores);
        $graph->Add($barplot);
        $graph->Stroke();
    }

    private function crearGraficoVacio($mensaje)
    {
        $img = imagecreatetruecolor(400, 300);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $textColor = imagecolorallocate($img, 50, 50, 50);
        imagefilledrectangle($img, 0, 0, 400, 300, $bg);
        imagestring($img, 5, 10, 130, $mensaje . " (sin datos)", $textColor);
        imagepng($img);
        imagedestroy($img);
    }
}