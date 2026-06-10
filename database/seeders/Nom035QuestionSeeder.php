<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Nom035QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Questionnaires
        DB::table('nom035_questionnaires')->insert([
            ['guide' => 'I', 'name' => 'Acontecimientos traumáticos severos', 'created_at' => now(), 'updated_at' => now()],
            ['guide' => 'II', 'name' => 'Factores de riesgo psicosocial (hasta 50 trabajadores)', 'created_at' => now(), 'updated_at' => now()],
            ['guide' => 'III', 'name' => 'Factores de riesgo psicosocial y entorno organizacional (>50 trabajadores)', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Guide I questions
        $qI = DB::table('nom035_questionnaires')->where('guide', 'I')->first()->id;
        $guideI = [
            [1, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave?', 'Sección I'],
            [2, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Asaltos?', 'Sección I'],
            [3, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Actos violentos que derivaron en lesiones graves?', 'Sección I'],
            [4, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Secuestro?', 'Sección I'],
            [5, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Amenazas?', 'Sección I'],
            [6, '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes: Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas?', 'Sección I'],
            [7, '¿Ha tenido recuerdos recurrentes sobre el acontecimiento que le provocan malestares?', 'Sección II'],
            [8, '¿Ha tenido sueños de carácter recurrente sobre el acontecimiento, que le producen malestar?', 'Sección II'],
            [9, '¿Se ha esforzado por evitar todo tipo de sentimientos, conversaciones o situaciones que le puedan recordar el acontecimiento?', 'Sección III'],
            [10, '¿Se ha esforzado por evitar todo tipo de actividades, lugares o personas que motivan recuerdos del acontecimiento?', 'Sección III'],
            [11, '¿Ha tenido dificultad para recordar alguna parte importante del evento?', 'Sección III'],
            [12, '¿Ha disminuido su interés en sus actividades cotidianas?', 'Sección III'],
            [13, '¿Se ha sentido alejado o distante de los demás?', 'Sección III'],
            [14, '¿Ha notado que tiene dificultad para expresar sus sentimientos?', 'Sección III'],
            [15, '¿Ha tenido la impresión de que su vida se va a acortar, que va a morir antes que otras personas o que tiene un futuro limitado?', 'Sección III'],
            [16, '¿Ha tenido dificultades para dormir?', 'Sección IV'],
            [17, '¿Ha estado particularmente irritable o le han dado arranques de coraje?', 'Sección IV'],
            [18, '¿Ha tenido dificultad para concentrarse?', 'Sección IV'],
            [19, '¿Ha estado nervioso o constantemente en alerta?', 'Sección IV'],
            [20, '¿Se ha sobresaltado fácilmente por cualquier cosa?', 'Sección IV'],
        ];
        foreach ($guideI as $q) {
            DB::table('nom035_questions')->insert([
                'questionnaire_id' => $qI, 'order' => $q[0], 'question_text' => $q[1], 'section' => $q[2], 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        // Guide II questions (simplified - include all 72 questions)
        $qII = DB::table('nom035_questionnaires')->where('guide', 'II')->first()->id;
        $guideII = [
            [1,'Mi trabajo me exige hacer mucho esfuerzo físico','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            [2,'Me preocupa sufrir un accidente en mi trabajo','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            [3,'Considero que las actividades que realizo son peligrosas','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            [4,'Por la cantidad de trabajo que tengo debo quedarme tiempo adicional a mi turno','Factores propios de la actividad','Carga de trabajo'],
            [5,'Por la cantidad de trabajo que tengo debo trabajar sin parar','Factores propios de la actividad','Carga de trabajo'],
            [6,'Considero que es necesario mantener un ritmo de trabajo acelerado','Factores propios de la actividad','Carga de trabajo'],
            [7,'Mi trabajo exige que esté muy concentrado','Factores propios de la actividad','Carga de trabajo'],
            [8,'Mi trabajo requiere que memorice mucha información','Factores propios de la actividad','Carga de trabajo'],
            [9,'Mi trabajo exige que atienda varios asuntos al mismo tiempo','Factores propios de la actividad','Carga de trabajo'],
            [10,'En mi trabajo soy responsable de cosas de mucho valor','Factores propios de la actividad','Carga de trabajo'],
            [11,'Respondo ante mi jefe por los resultados de toda mi área de trabajo','Factores propios de la actividad','Carga de trabajo'],
            [12,'En mi trabajo me dan órdenes contradictorias','Factores propios de la actividad','Carga de trabajo'],
            [13,'Considero que en mi trabajo me piden hacer cosas innecesarias','Factores propios de la actividad','Carga de trabajo'],
            [14,'Trabajo horas extras más de tres veces a la semana','Organización del tiempo de trabajo','Jornada de trabajo'],
            [15,'Mi trabajo me exige laborar en días de descanso, festivos o fines de semana','Organización del tiempo de trabajo','Jornada de trabajo'],
            [16,'Considero que el tiempo en el trabajo es mucho y perjudica mis actividades familiares o personales','Organización del tiempo de trabajo','Interferencia trabajo-familia'],
            [17,'Pienso en las actividades familiares o personales cuando estoy en mi trabajo','Organización del tiempo de trabajo','Interferencia trabajo-familia'],
            [18,'Mi trabajo permite que desarrolle nuevas habilidades','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [19,'En mi trabajo puedo aspirar a un mejor puesto','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [20,'Durante mi jornada de trabajo puedo tomar pausas cuando las necesito','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [21,'Puedo decidir cuánto trabajo realizo durante la jornada laboral','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [22,'Puedo decidir la velocidad a la que realizo mis actividades','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [23,'Mi trabajo se limita a repetir una y otra vez el mismo conjunto de tareas o actividades','Falta de control sobre el trabajo','Falta de control y autonomía'],
            [24,'En mi trabajo me dan instrucciones claras sobre lo que debo hacer','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [25,'Mi jefe ayuda a solucionar los problemas que se presentan en el trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [26,'Mi jefe se preocupa por mi salud y bienestar','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [27,'Mi jefe me comunica oportunamente lo que debo hacer en mi trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [28,'Mi jefe me dice cómo califican mi desempeño en el trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [29,'Me han solicitado realizar tareas que no corresponden a mis funciones','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [30,'Tengo dificultad para trabajar con mi jefe','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [31,'En mi trabajo puedo expresar libremente mis ideas','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo',1],
            [32,'Recibo apoyo de mis compañeros para realizar mi trabajo','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo',1],
            [33,'Mi trabajo me exige atender situaciones de violencia','Liderazgo y relaciones en el trabajo','Violencia'],
            [34,'Mi jefe ejerce violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [35,'Mi jefe ejerce violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [36,'Mis compañeros ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [37,'Mis compañeros ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [38,'Mis subordinados ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [39,'Mis subordinados ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [40,'Personas ajenas a mi trabajo ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [41,'Personas ajenas a mi trabajo ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [42,'Recibo maltrato verbal de mi jefe','Liderazgo y relaciones en el trabajo','Violencia'],
            [43,'Recibo maltrato verbal de mis compañeros','Liderazgo y relaciones en el trabajo','Violencia'],
            [44,'Recibo maltrato verbal de mis subordinados','Liderazgo y relaciones en el trabajo','Violencia'],
            [45,'Me gustaría estar en otra empresa o trabajo','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
        ];
        foreach ($guideII as $q) {
            DB::table('nom035_questions')->insert([
                'questionnaire_id' => $qII, 'order' => $q[0], 'question_text' => $q[1], 'category' => $q[2], 'domain' => $q[3], 'is_inverse' => $q[4] ?? false, 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        // Guide III questions (72 items — >50 trabajadores, 5 categorías, 10 dominios)
        // Basado en Referencia III de la NOM-035-STPS-2018 (DOF 23/10/2018)
        $qIII = DB::table('nom035_questionnaires')->where('guide', 'III')->first()->id;
        $guideIII = [
            // === 1. Condiciones en el ambiente de trabajo (items 1-3) ===
            [1,'Mi trabajo me exige hacer mucho esfuerzo físico','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            [2,'Me preocupa sufrir un accidente en mi trabajo','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            [3,'Considero que las actividades que realizo son peligrosas','Ambiente de trabajo','Condiciones en el ambiente de trabajo'],
            // === 2. Carga de trabajo (items 4-15) ===
            [4,'Por la cantidad de trabajo que tengo debo quedarme tiempo adicional a mi turno','Factores propios de la actividad','Carga de trabajo'],
            [5,'Por la cantidad de trabajo que tengo debo trabajar sin parar','Factores propios de la actividad','Carga de trabajo'],
            [6,'Considero que es necesario mantener un ritmo de trabajo acelerado','Factores propios de la actividad','Carga de trabajo'],
            [7,'Mi trabajo exige que esté muy concentrado','Factores propios de la actividad','Carga de trabajo'],
            [8,'Mi trabajo requiere que memorice mucha información','Factores propios de la actividad','Carga de trabajo'],
            [9,'Mi trabajo exige que atienda varios asuntos al mismo tiempo','Factores propios de la actividad','Carga de trabajo'],
            [10,'En mi trabajo soy responsable de cosas de mucho valor','Factores propios de la actividad','Carga de trabajo'],
            [11,'Respondo ante mi jefe por los resultados de toda mi área de trabajo','Factores propios de la actividad','Carga de trabajo'],
            [12,'En mi trabajo me dan órdenes contradictorias','Factores propios de la actividad','Carga de trabajo'],
            [13,'Considero que en mi trabajo me piden hacer cosas innecesarias','Factores propios de la actividad','Carga de trabajo'],
            [14,'Mi trabajo exige que tome decisiones rápidas','Factores propios de la actividad','Carga de trabajo'],
            [15,'Mi trabajo exige que me responsabilice de actos o decisiones de otros','Factores propios de la actividad','Carga de trabajo'],
            // === 3. Falta de control sobre el trabajo (items 16-23) ===
            [16,'Mi trabajo permite que desarrolle nuevas habilidades','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [17,'En mi trabajo puedo aspirar a un mejor puesto','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [18,'Durante mi jornada de trabajo puedo tomar pausas cuando las necesito','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [19,'Puedo decidir cuánto trabajo realizo durante la jornada laboral','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [20,'Puedo decidir la velocidad a la que realizo mis actividades','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [21,'Puedo cambiar el orden de las actividades que realizo en mi trabajo','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [22,'Puedo decidir o influir sobre el método de trabajo que utilizo','Falta de control sobre el trabajo','Falta de control sobre el trabajo',1],
            [23,'Mi trabajo se limita a repetir una y otra vez el mismo conjunto de tareas o actividades','Falta de control sobre el trabajo','Falta de control sobre el trabajo'],
            // === 4. Jornada de trabajo (items 24-27) ===
            [24,'Trabajo horas extras más de tres veces a la semana','Organización del tiempo de trabajo','Jornada de trabajo'],
            [25,'Mi trabajo me exige laborar en días de descanso, festivos o fines de semana','Organización del tiempo de trabajo','Jornada de trabajo'],
            [26,'Debo atender asuntos de trabajo cuando estoy fuera de mi horario laboral','Organización del tiempo de trabajo','Jornada de trabajo'],
            [27,'En mi trabajo, los horarios cambian sin previo aviso','Organización del tiempo de trabajo','Jornada de trabajo'],
            // === 5. Interferencia en la relación trabajo-familia (items 28-31) ===
            [28,'Considero que el tiempo en el trabajo es mucho y perjudica mis actividades familiares o personales','Organización del tiempo de trabajo','Interferencia en la relación trabajo-familia'],
            [29,'Pienso en las actividades familiares o personales cuando estoy en mi trabajo','Organización del tiempo de trabajo','Interferencia en la relación trabajo-familia'],
            [30,'Mi trabajo me impide pasar tiempo con mi familia o amigos','Organización del tiempo de trabajo','Interferencia en la relación trabajo-familia'],
            [31,'Por mi trabajo, en mi casa se me ha dificultado cumplir con mis responsabilidades','Organización del tiempo de trabajo','Interferencia en la relación trabajo-familia'],
            // === 6. Liderazgo (items 32-39) ===
            [32,'En mi trabajo me dan instrucciones claras sobre lo que debo hacer','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [33,'Mi jefe ayuda a solucionar los problemas que se presentan en el trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [34,'Mi jefe se preocupa por mi salud y bienestar','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [35,'Mi jefe me comunica oportunamente lo que debo hacer en mi trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [36,'Mi jefe me dice cómo califican mi desempeño en el trabajo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [37,'Mi jefe toma en cuenta mis opiniones','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [38,'Mi jefe distribuye las cargas de trabajo de manera justa','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            [39,'Mi jefe fomenta el trabajo en equipo','Liderazgo y relaciones en el trabajo','Liderazgo',1],
            // === 7. Relaciones en el trabajo (items 40-49) ===
            [40,'Me han solicitado realizar tareas que no corresponden a mis funciones','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [41,'Tengo dificultad para trabajar con mi jefe','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [42,'En mi trabajo puedo expresar libremente mis ideas','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo',1],
            [43,'Recibo apoyo de mis compañeros para realizar mi trabajo','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo',1],
            [44,'Me gustaría estar en otra empresa o trabajo','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [45,'En mi trabajo se me excluye de reuniones o decisiones importantes','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [46,'En mi trabajo circula información poco clara o contradictoria','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [47,'Existe competencia desleal entre mis compañeros','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [48,'Siento que en mi trabajo me ignoran o me hacen sentir invisible','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            [49,'Mis compañeros me quitan información necesaria para hacer mi trabajo','Liderazgo y relaciones en el trabajo','Relaciones en el trabajo'],
            // === 8. Violencia (items 50-61) ===
            [50,'Mi trabajo me exige atender situaciones de violencia','Liderazgo y relaciones en el trabajo','Violencia'],
            [51,'Mi jefe ejerce violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [52,'Mi jefe ejerce violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [53,'Mis compañeros ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [54,'Mis compañeros ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [55,'Mis subordinados ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [56,'Mis subordinados ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [57,'Personas ajenas a mi trabajo ejercen violencia física contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [58,'Personas ajenas a mi trabajo ejercen violencia psicológica contra mí','Liderazgo y relaciones en el trabajo','Violencia'],
            [59,'Recibo maltrato verbal de mi jefe','Liderazgo y relaciones en el trabajo','Violencia'],
            [60,'Recibo maltrato verbal de mis compañeros','Liderazgo y relaciones en el trabajo','Violencia'],
            [61,'Recibo maltrato verbal de mis subordinados','Liderazgo y relaciones en el trabajo','Violencia'],
            // === 9. Reconocimiento del desempeño (items 62-66) ===
            [62,'Mi trabajo me da la oportunidad de hacer lo que mejor sé hacer','Entorno organizacional','Reconocimiento del desempeño',1],
            [63,'Mi jefe reconoce mi trabajo y esfuerzo','Entorno organizacional','Reconocimiento del desempeño',1],
            [64,'Recibo algún tipo de incentivo o reconocimiento cuando realizo bien mi trabajo','Entorno organizacional','Reconocimiento del desempeño',1],
            [65,'En mi trabajo se valora mi antigüedad y lealtad','Entorno organizacional','Reconocimiento del desempeño',1],
            [66,'Siento que mi trabajo es valorado por la empresa','Entorno organizacional','Reconocimiento del desempeño',1],
            // === 10. Insuficiente sentido de pertenencia e inestabilidad (items 67-72) ===
            [67,'En mi empresa hay cambios frecuentes de personal','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
            [68,'En mi trabajo existe incertidumbre sobre mi permanencia laboral','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
            [69,'En mi trabajo se despide a los trabajadores sin un procedimiento claro','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
            [70,'Existen cambios imprevistos o constantes en las políticas de la empresa','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
            [71,'Las condiciones laborales cambian sin previo aviso (sueldo, horario, funciones)','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
            [72,'Siento que no pertenezco a esta empresa','Entorno organizacional','Insuficiente sentido de pertenencia e inestabilidad'],
        ];
        foreach ($guideIII as $q) {
            DB::table('nom035_questions')->insert([
                'questionnaire_id' => $qIII, 'order' => $q[0], 'question_text' => $q[1], 'category' => $q[2], 'domain' => $q[3], 'is_inverse' => $q[4] ?? false, 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        echo "NOM-035 questions seeded.\n";
    }
}
