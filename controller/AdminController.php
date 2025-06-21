<?php
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
class AdminController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function show()
    {
        $usuario = $_SESSION["username"] ?? null;
        $filtro = $_GET['filtro_fecha'] ?? 'semana';

        $data = [
            "usuario" => $this->model->getUserByUsername($usuario),
            'filtroSeleccionado' => $filtro,
            'filtroDia' => $filtro === 'dia',
            'filtroSemana' => $filtro === 'semana',
            'filtroMes' => $filtro === 'mes',
            'filtroAño' => $filtro === 'año',
            'filtroTodos' => $filtro === 'todos',
        ];

        $this->view->render('adminDashboard', $data);
    }

    public function exportarPDF()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'semana';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            h2 {
                text-align: center;
                margin-bottom: 30px;
            }
            table {
                width: 100%;
                border-spacing: 20px;
            }
            td {
                width: 50%;
                text-align: center;
                vertical-align: top;
                background-color: #f9f9f9;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 8px;
            }
            img {
                width: 100%;
                height: auto;
            }
            h3 {
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <h2>Reporte de Usuarios (Filtro: ' . htmlspecialchars($filtro) . ')</h2>
        <table>
            <tr>
                <td>
                    <h3>Usuarios por País</h3>
                    <img src="http://localhost/QuizGame/public/graficos/usuarios_por_pais.png" />
                </td>
                <td>
                    <h3>Usuarios por Sexo</h3>
                    <img src="http://localhost/QuizGame/public/graficos/usuarios_por_sexo.png" />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <h3>Usuarios por Grupo Etario</h3>
                    <img src="http://localhost/QuizGame/public/graficos/usuarios_por_grupo.png" />
                </td>
            </tr>
        </table>
    </body>
    </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("reporte_usuarios.pdf", ["Attachment" => false]);
    }



}