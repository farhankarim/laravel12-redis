<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populates every table that the master-report query joins across so that
 * GET /api/v1/students/{id}/report returns real rows.
 *
 * Tables seeded (in insertion order):
 *   departments, instructors, classrooms, courses, students,
 *   course_assignments, department_faculty, course_schedules, enrollments
 */
class MasterReportSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ------------------------------------------------------------------
        // 1. Departments
        // ------------------------------------------------------------------
        $deptIds = [];
        foreach ([
            'Computer Science',
            'Mathematics',
            'Physics',
        ] as $name) {
            $deptIds[$name] = DB::table('departments')->insertGetId([
                'dept_name'  => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ------------------------------------------------------------------
        // 2. Instructors
        // ------------------------------------------------------------------
        $instrIds = [];
        foreach ([
            ['name' => 'Dr. Alice Smith',   'specialization' => 'Algorithms & Data Structures'],
            ['name' => 'Dr. Bob Jones',     'specialization' => 'Calculus & Linear Algebra'],
            ['name' => 'Dr. Carol White',   'specialization' => 'Quantum Mechanics'],
        ] as $row) {
            $instrIds[$row['name']] = DB::table('instructors')->insertGetId([
                'name'           => $row['name'],
                'specialization' => $row['specialization'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // ------------------------------------------------------------------
        // 3. Classrooms
        // ------------------------------------------------------------------
        $roomIds = [];
        foreach ([
            ['room_number' => 'A101', 'building' => 'Main Hall'],
            ['room_number' => 'B202', 'building' => 'Science Block'],
            ['room_number' => 'C303', 'building' => 'Engineering Wing'],
        ] as $row) {
            $roomIds[$row['room_number']] = DB::table('classrooms')->insertGetId([
                'room_number' => $row['room_number'],
                'building'    => $row['building'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // ------------------------------------------------------------------
        // 4. Courses
        // ------------------------------------------------------------------
        $courseIds = [];
        foreach ([
            ['course_code' => 'CS101',   'title' => 'Introduction to Computer Science', 'credit_hours' => 3],
            ['course_code' => 'MATH201', 'title' => 'Calculus II', 'credit_hours' => 4],
            ['course_code' => 'PHYS301', 'title' => 'Quantum Mechanics I', 'credit_hours' => 3],
        ] as $row) {
            $courseIds[$row['course_code']] = DB::table('courses')->insertGetId([
                'course_code' => $row['course_code'],
                'title'       => $row['title'],
                'credit_hours'=> $row['credit_hours'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // ------------------------------------------------------------------
        // 5. Students
        // ------------------------------------------------------------------
        $studentIds = [];
        foreach ([
            ['name' => 'Alice Johnson', 'email' => 'alice@university.test'],
            ['name' => 'Bob Martinez',  'email' => 'bob@university.test'],
            ['name' => 'Carol Lee',     'email' => 'carol@university.test'],
        ] as $row) {
            $studentIds[$row['name']] = DB::table('students')->insertGetId([
                'name'       => $row['name'],
                'email'      => $row['email'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ------------------------------------------------------------------
        // 6. course_assignments  (instructor → course)
        // ------------------------------------------------------------------
        DB::table('course_assignments')->insert([
            ['instructor_id' => $instrIds['Dr. Alice Smith'], 'course_id' => $courseIds['CS101'],   'created_at' => $now, 'updated_at' => $now],
            ['instructor_id' => $instrIds['Dr. Bob Jones'],   'course_id' => $courseIds['MATH201'], 'created_at' => $now, 'updated_at' => $now],
            ['instructor_id' => $instrIds['Dr. Carol White'], 'course_id' => $courseIds['PHYS301'], 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ------------------------------------------------------------------
        // 7. department_faculty  (department → instructor)
        // ------------------------------------------------------------------
        DB::table('department_faculty')->insert([
            ['department_id' => $deptIds['Computer Science'], 'instructor_id' => $instrIds['Dr. Alice Smith'], 'created_at' => $now, 'updated_at' => $now],
            ['department_id' => $deptIds['Mathematics'],      'instructor_id' => $instrIds['Dr. Bob Jones'],   'created_at' => $now, 'updated_at' => $now],
            ['department_id' => $deptIds['Physics'],          'instructor_id' => $instrIds['Dr. Carol White'], 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ------------------------------------------------------------------
        // 8. course_schedules  (course → classroom + schedule)
        // ------------------------------------------------------------------
        DB::table('course_schedules')->insert([
            ['course_id' => $courseIds['CS101'],   'classroom_id' => $roomIds['A101'], 'day_of_week' => 'Monday',    'start_time' => '09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $courseIds['MATH201'], 'classroom_id' => $roomIds['B202'], 'day_of_week' => 'Tuesday',   'start_time' => '10:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $courseIds['PHYS301'], 'classroom_id' => $roomIds['C303'], 'day_of_week' => 'Wednesday', 'start_time' => '11:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ------------------------------------------------------------------
        // 9. enrollments  (student → course + semester + grade)
        // ------------------------------------------------------------------
        DB::table('enrollments')->insert([
            // Alice is enrolled in all three courses
            ['student_id' => $studentIds['Alice Johnson'], 'course_id' => $courseIds['CS101'],   'semester' => '2024-Fall', 'grade' => 'A',  'created_at' => $now, 'updated_at' => $now],
            ['student_id' => $studentIds['Alice Johnson'], 'course_id' => $courseIds['MATH201'], 'semester' => '2024-Fall', 'grade' => 'B+', 'created_at' => $now, 'updated_at' => $now],
            ['student_id' => $studentIds['Alice Johnson'], 'course_id' => $courseIds['PHYS301'], 'semester' => '2024-Fall', 'grade' => 'A-', 'created_at' => $now, 'updated_at' => $now],
            // Bob is enrolled in two courses
            ['student_id' => $studentIds['Bob Martinez'],  'course_id' => $courseIds['CS101'],   'semester' => '2024-Fall', 'grade' => 'B',  'created_at' => $now, 'updated_at' => $now],
            ['student_id' => $studentIds['Bob Martinez'],  'course_id' => $courseIds['MATH201'], 'semester' => '2024-Fall', 'grade' => 'B-', 'created_at' => $now, 'updated_at' => $now],
            // Carol is enrolled in one course
            ['student_id' => $studentIds['Carol Lee'],     'course_id' => $courseIds['PHYS301'], 'semester' => '2024-Fall', 'grade' => 'A',  'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
