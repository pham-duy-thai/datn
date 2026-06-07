<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Banner;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\News;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->resetBusinessTables();

        $users = $this->seedUsers();
        $departments = $this->seedDepartments();
        $doctors = $this->seedDoctors($users, $departments);
        $schedules = $this->seedDoctorSchedules($doctors);
        $services = $this->seedServices($departments);
        $appointments = $this->seedAppointments($users, $doctors, $schedules, $services);
        $this->seedPayments($appointments);

        $this->seedMedicalRecords($appointments);
        $this->seedNews($users['admin@hospital.test']);
        $this->seedContacts();
        $this->seedBanners();
    }

    private function resetBusinessTables(): void
    {
        Schema::disableForeignKeyConstraints();

        MedicalRecord::truncate();
        Payment::truncate();
        Appointment::truncate();
        DoctorSchedule::truncate();
        Contact::truncate();
        News::truncate();
        Banner::truncate();
        Service::truncate();
        Doctor::truncate();
        Department::truncate();
        User::truncate();

        Schema::enableForeignKeyConstraints();
    }

    private function seedUsers(): array
    {
        $records = [
            ['Quản trị viên bệnh viện', 'admin@hospital.test', '0900000001', 'admin', 'other', null, 'Hà Nội'],
            ['Nguyễn Thúy An', 'patient@hospital.test', '0900000002', 'patient', 'female', '1998-05-20', 'Hà Nội'],
            ['Phạm Ngọc Lan', 'receptionist@hospital.test', '0900000035', 'receptionist', 'female', '1992-12-03', 'Hà Nội'],
            ['Trần Minh Khang', 'doctor.timmach@hospital.test', '0900000003', 'doctor', 'male', '1984-03-12', 'Hà Nội'],
            ['Lê Hoài Phương', 'doctor.nhikhoa@hospital.test', '0900000004', 'doctor', 'female', '1987-08-22', 'Đà Nẵng'],
            ['Phạm Anh Tuấn', 'doctor.noitiet@hospital.test', '0900000005', 'doctor', 'male', '1981-11-02', 'Thành phố Hồ Chí Minh'],
            ['Đỗ Thanh Hương', 'doctor.sanphu@hospital.test', '0900000006', 'doctor', 'female', '1986-01-15', 'Hải Phòng'],
            ['Vũ Quốc Huy', 'doctor.mat@hospital.test', '0900000007', 'doctor', 'male', '1980-09-09', 'Cần Thơ'],
            ['Bùi Kim Ngân', 'doctor.ranghammat@hospital.test', '0900000008', 'doctor', 'female', '1989-04-18', 'Huế'],
            ['Đặng Minh Châu', 'dangminhchau@example.com', '0900000009', 'patient', 'female', '1994-07-14', 'Hà Nội'],
            ['Hoàng Gia Bảo', 'hoanggiabao@example.com', '0900000010', 'patient', 'male', '1991-10-05', 'Bắc Ninh'],
            ['Võ Thanh Hằng', 'vothanhhang@example.com', '0900000011', 'patient', 'female', '1988-12-25', 'Quảng Ninh'],
            ['Phan Đức Mạnh', 'phanducmanh@example.com', '0900000012', 'patient', 'male', '1996-02-11', 'Nghệ An'],
            ['Mai Khánh Linh', 'maikhanhlinh@example.com', '0900000013', 'patient', 'female', '2000-06-30', 'Thanh Hóa'],
            ['Trương Hải Nam', 'truonghainam@example.com', '0900000014', 'patient', 'male', '1992-04-27', 'Đồng Nai'],
            ['Ngô Bảo Trâm', 'ngobaotram@example.com', '0900000015', 'patient', 'female', '1999-09-17', 'Bình Dương'],
            ['Đinh Nhật Minh', 'dinhnhatminh@example.com', '0900000016', 'patient', 'male', '1987-01-07', 'Lâm Đồng'],
            ['Lý Thu Hà', 'lythuha@example.com', '0900000017', 'patient', 'female', '1995-03-19', 'Nam Định'],
            ['Cao Minh Quân', 'caominhquan@example.com', '0900000018', 'patient', 'male', '1990-08-08', 'Khánh Hòa'],
            ['Hồ Ngọc Diệp', 'hongocdiep@example.com', '0900000019', 'patient', 'female', '1993-05-23', 'Bến Tre'],
        ];

        $users = [];

        foreach ($records as [$name, $email, $phone, $role, $gender, $dateOfBirth, $address]) {
            $users[$email] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'role' => $role,
                    'gender' => $gender,
                    'date_of_birth' => $dateOfBirth,
                    'address' => $address,
                    'password' => 'password',
                ]
            );
        }

        return $users;
    }

    private function seedDepartments(): array
    {
        $records = [
            ['tim-mach', 'Tim mạch', 'Khoa khám và điều trị các bệnh lý về tim mạch.', 'images/frontend/about-bg.jpg'],
            ['nhi-khoa', 'Nhi khoa', 'Khoa chăm sóc sức khỏe trẻ em và trẻ sơ sinh.', 'images/frontend/slider2.jpg'],
            ['da-lieu', 'Da liễu', 'Khoa khám và điều trị các vấn đề về da.', 'images/frontend/slider3.jpg'],
            ['noi-tong-quat', 'Nội tổng quát', 'Khoa tiếp nhận khám nội khoa, theo dõi bệnh mạn tính và tư vấn sức khỏe.', 'images/frontend/slider1.jpg'],
            ['ngoai-tong-quat', 'Ngoại tổng quát', 'Khoa thăm khám, xử trí và theo dõi các bệnh lý ngoại khoa thường gặp.', 'images/frontend/appointment-image.jpg'],
            ['tai-mui-hong', 'Tai mũi họng', 'Khoa chẩn đoán và điều trị bệnh lý tai, mũi, họng.', 'images/frontend/news-image.jpg'],
            ['mat', 'Mắt', 'Khoa khám mắt, đo thị lực và theo dõi các bệnh lý nhãn khoa.', 'images/frontend/news-image1.jpg'],
            ['rang-ham-mat', 'Răng hàm mặt', 'Khoa chăm sóc răng miệng, nha chu và tư vấn chỉnh nha.', 'images/frontend/news-image2.jpg'],
            ['san-phu-khoa', 'Sản phụ khoa', 'Khoa khám thai, phụ khoa và chăm sóc sức khỏe sinh sản.', 'images/frontend/news-image3.jpg'],
            ['co-xuong-khop', 'Cơ xương khớp', 'Khoa khám đau khớp, thoái hóa khớp và các bệnh lý vận động.', 'images/frontend/about-bg.jpg'],
            ['than-kinh', 'Thần kinh', 'Khoa theo dõi bệnh lý thần kinh, đau đầu, rối loạn giấc ngủ.', 'images/frontend/slider2.jpg'],
            ['noi-tiet', 'Nội tiết', 'Khoa khám bệnh tiểu đường, tuyến giáp và rối loạn chuyển hóa.', 'images/frontend/slider3.jpg'],
            ['tieu-hoa', 'Tiêu hóa', 'Khoa chẩn đoán và điều trị bệnh lý dạ dày, gan mật, đại tràng.', 'images/frontend/appointment-image.jpg'],
            ['ho-hap', 'Hô hấp', 'Khoa khám hen, viêm phế quản, phổi tắc nghẽn và hậu COVID.', 'images/frontend/news-image1.jpg'],
            ['than-tiet-nieu', 'Thận tiết niệu', 'Khoa khám bệnh lý thận, tiết niệu và tư vấn dự phòng sỏi.', 'images/frontend/news-image2.jpg'],
            ['ung-buou', 'Ung bướu', 'Khoa tầm soát, tư vấn và theo dõi điều trị ung bướu.', 'images/frontend/news-image3.jpg'],
            ['phuc-hoi-chuc-nang', 'Phục hồi chức năng', 'Khoa vật lý trị liệu, phục hồi vận động và giảm đau.', 'images/frontend/about-bg.jpg'],
            ['cap-cuu', 'Cấp cứu', 'Khoa tiếp nhận, phân loại và xử trí cấp cứu ban đầu.', 'images/frontend/slider1.jpg'],
            ['dinh-duong', 'Dinh dưỡng', 'Khoa tư vấn chế độ ăn, kiểm soát cân nặng và dinh dưỡng bệnh lý.', 'images/frontend/news-image.jpg'],
            ['tam-ly-lam-sang', 'Tâm lý lâm sàng', 'Khoa tư vấn sức khỏe tinh thần, stress và rối loạn giấc ngủ.', 'images/frontend/slider2.jpg'],
        ];

        $departments = [];

        foreach ($records as [$slug, $name, $description, $image]) {
            $departments[$slug] = Department::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'is_active' => true,
                ]
            );
        }

        return $departments;
    }

    private function seedDoctors(array $users, array $departments): array
    {
        $records = [
            ['doctor.timmach@hospital.test', 'BS. Trần Minh Khang', '0900000003', 'tim-mach', 'Tim mạch can thiệp', 'Thạc sĩ, Bác sĩ chuyên khoa I', 12, 'Có kinh nghiệm điều trị tăng huyết áp, suy tim và bệnh mạch vành.', 320000, 'images/frontend/team-image1.jpg'],
            ['doctor.nhikhoa@hospital.test', 'BS. Lê Hoài Phương', '0900000004', 'nhi-khoa', 'Nhi tổng quát', 'Bác sĩ chuyên khoa I', 9, 'Chuyên khám các bệnh thường gặp ở trẻ em và tư vấn chăm sóc trẻ sơ sinh.', 260000, 'images/frontend/team-image2.jpg'],
            ['doctor.noitiet@hospital.test', 'TS.BS. Phạm Anh Tuấn', '0900000005', 'noi-tiet', 'Nội tiết và đái tháo đường', 'Tiến sĩ, Bác sĩ chuyên khoa II', 16, 'Theo dõi bệnh tiểu đường, tuyến giáp và rối loạn chuyển hóa.', 380000, 'images/frontend/team-image3.jpg'],
            ['doctor.sanphu@hospital.test', 'BS. Đỗ Thanh Hương', '0900000006', 'san-phu-khoa', 'Sản phụ khoa', 'Bác sĩ chuyên khoa II', 14, 'Khám thai định kỳ, tư vấn phụ khoa và chăm sóc sức khỏe sinh sản.', 350000, 'images/frontend/team-image1.jpg'],
            ['doctor.mat@hospital.test', 'BS. Vũ Quốc Huy', '0900000007', 'mat', 'Nhãn khoa tổng quát', 'Thạc sĩ, Bác sĩ', 11, 'Khám mắt, đo thị lực và theo dõi bệnh lý võng mạc.', 240000, 'images/frontend/team-image2.jpg'],
            ['doctor.ranghammat@hospital.test', 'BS. Bùi Kim Ngân', '0900000008', 'rang-ham-mat', 'Nha khoa phục hồi', 'Bác sĩ Răng hàm mặt', 8, 'Tư vấn chăm sóc răng miệng, nha chu và phục hình thẩm mỹ.', 220000, 'images/frontend/team-image3.jpg'],
            ['doctor.noi@hospital.test', 'BS. Nguyễn Hữu Duy', '0900000021', 'noi-tong-quat', 'Nội khoa tổng quát', 'Bác sĩ chuyên khoa I', 10, 'Khám tổng quát, quản lý bệnh mạn tính và tư vấn phòng bệnh.', 230000, 'images/frontend/team-image1.jpg'],
            ['doctor.ngoai@hospital.test', 'BS. Đặng Thu Trang', '0900000022', 'ngoai-tong-quat', 'Ngoại tổng quát', 'Thạc sĩ, Bác sĩ', 13, 'Đánh giá bệnh lý ngoại khoa và tư vấn kế hoạch điều trị phù hợp.', 300000, 'images/frontend/team-image2.jpg'],
            ['doctor.taimuihong@hospital.test', 'BS. Hoàng Việt Anh', '0900000023', 'tai-mui-hong', 'Tai mũi họng', 'Bác sĩ chuyên khoa I', 7, 'Khám viêm xoang, viêm họng, ù tai và rối loạn giọng nói.', 210000, 'images/frontend/team-image3.jpg'],
            ['doctor.coxuongkhop@hospital.test', 'TS.BS. Mai Phương Linh', '0900000024', 'co-xuong-khop', 'Cơ xương khớp', 'Tiến sĩ, Bác sĩ', 15, 'Điều trị đau khớp, viêm khớp và thoái hóa cột sống.', 360000, 'images/frontend/team-image1.jpg'],
            ['doctor.thankinh@hospital.test', 'BS. Cao Đức Bình', '0900000025', 'than-kinh', 'Thần kinh', 'Bác sĩ chuyên khoa II', 18, 'Khám đau đầu, tê bì tay chân, rối loạn tiền đình và giấc ngủ.', 370000, 'images/frontend/team-image2.jpg'],
            ['doctor.tieuhoa@hospital.test', 'BS. Lương Ngọc Hà', '0900000026', 'tieu-hoa', 'Tiêu hóa gan mật', 'Thạc sĩ, Bác sĩ', 12, 'Khám đau dạ dày, gan mật, đại tràng và tư vấn nội soi.', 330000, 'images/frontend/team-image3.jpg'],
            ['doctor.hohap@hospital.test', 'BS. Trịnh Khánh Toàn', '0900000027', 'ho-hap', 'Hô hấp', 'Bác sĩ chuyên khoa I', 9, 'Theo dõi hen, viêm phế quản và bệnh phổi tắc nghẽn.', 270000, 'images/frontend/team-image1.jpg'],
            ['doctor.thantietnieu@hospital.test', 'BS. Võ Mỹ Duyên', '0900000028', 'than-tiet-nieu', 'Thận tiết niệu', 'Bác sĩ chuyên khoa I', 10, 'Khám bệnh thận, sỏi tiết niệu và nhiễm khuẩn đường tiểu.', 280000, 'images/frontend/team-image2.jpg'],
            ['doctor.ungbuou@hospital.test', 'BS. Hồ Minh Tâm', '0900000029', 'ung-buou', 'Ung bướu', 'Thạc sĩ, Bác sĩ chuyên khoa II', 17, 'Tư vấn tầm soát ung thư và theo dõi sau điều trị.', 420000, 'images/frontend/team-image3.jpg'],
            ['doctor.phuchoi@hospital.test', 'BS. Tạ Thu Thảo', '0900000030', 'phuc-hoi-chuc-nang', 'Phục hồi chức năng', 'Bác sĩ chuyên khoa I', 8, 'Lập kế hoạch vật lý trị liệu và phục hồi vận động sau chấn thương.', 250000, 'images/frontend/team-image1.jpg'],
            ['doctor.capcuu@hospital.test', 'BS. Đinh Gia Hưng', '0900000031', 'cap-cuu', 'Cấp cứu ban đầu', 'Bác sĩ cấp cứu', 11, 'Phân loại, xử trí ban đầu và theo dõi người bệnh cấp cứu.', 300000, 'images/frontend/team-image2.jpg'],
            ['doctor.dinhduong@hospital.test', 'BS. Châu Ngọc Mai', '0900000032', 'dinh-duong', 'Dinh dưỡng lâm sàng', 'Thạc sĩ, Bác sĩ', 9, 'Tư vấn dinh dưỡng bệnh lý, kiểm soát cân nặng và phục hồi sức khỏe.', 230000, 'images/frontend/team-image3.jpg'],
            ['doctor.tamly@hospital.test', 'ThS. Nguyễn Bảo Châu', '0900000033', 'tam-ly-lam-sang', 'Tâm lý lâm sàng', 'Thạc sĩ tâm lý lâm sàng', 10, 'Tư vấn stress, lo âu, mất ngủ và sức khỏe tinh thần.', 300000, 'images/frontend/team-image1.jpg'],
            ['doctor.dalieu@hospital.test', 'BS. Lê Thanh Tùng', '0900000034', 'da-lieu', 'Da liễu thẩm mỹ', 'Bác sĩ chuyên khoa I', 12, 'Khám mụn, viêm da, dị ứng và tư vấn chăm sóc da.', 260000, 'images/frontend/team-image2.jpg'],
        ];

        $doctors = [];

        foreach ($records as [$email, $name, $phone, $departmentSlug, $specialization, $degree, $experienceYears, $bio, $fee, $avatar]) {
            $doctors[$email] = Doctor::updateOrCreate(
                ['email' => $email],
                [
                    'user_id' => $users[$email]->id ?? null,
                    'department_id' => $departments[$departmentSlug]->id,
                    'name' => $name,
                    'phone' => $phone,
                    'avatar' => $avatar,
                    'specialization' => $specialization,
                    'degree' => $degree,
                    'experience_years' => $experienceYears,
                    'bio' => $bio,
                    'consultation_fee' => $fee,
                    'is_active' => true,
                ]
            );
        }

        return $doctors;
    }

    private function seedDoctorSchedules(array $doctors): array
    {
        $doctorList = array_values($doctors);
        $records = [
            [0, 1, '08:00', '11:30', 'A101', 12],
            [0, 3, '13:30', '17:00', 'A101', 10],
            [1, 2, '08:00', '11:30', 'B201', 14],
            [1, 4, '13:30', '17:00', 'B201', 12],
            [2, 1, '07:30', '11:00', 'C301', 10],
            [3, 2, '13:00', '16:30', 'C302', 10],
            [4, 3, '08:00', '11:30', 'D401', 12],
            [5, 4, '13:30', '17:00', 'D402', 12],
            [6, 5, '08:00', '11:30', 'A102', 15],
            [7, 6, '08:00', '11:00', 'A103', 8],
            [8, 1, '14:00', '17:00', 'B202', 10],
            [9, 2, '08:30', '11:30', 'B203', 10],
            [10, 3, '13:30', '16:30', 'C303', 8],
            [11, 4, '08:00', '11:30', 'C304', 12],
            [12, 5, '13:30', '17:00', 'D403', 12],
            [13, 6, '08:00', '11:30', 'D404', 10],
            [14, 7, '08:00', '11:00', 'E501', 8],
            [15, 1, '13:30', '16:30', 'E502', 9],
            [16, 2, '08:00', '11:30', 'F601', 12],
            [17, 3, '13:30', '17:00', 'F602', 10],
        ];

        $schedules = [];

        foreach ($records as [$doctorIndex, $weekday, $startTime, $endTime, $room, $maxPatients]) {
            $doctor = $doctorList[$doctorIndex];

            $schedules[] = DoctorSchedule::updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'weekday' => $weekday,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                [
                    'room' => $room,
                    'max_patients' => $maxPatients,
                    'is_available' => true,
                ]
            );
        }

        return $schedules;
    }

    private function seedServices(array $departments): array
    {
        $records = [
            ['kham-tim-mach', 'Khám tim mạch', 'tim-mach', 'Tư vấn, khám và đánh giá sức khỏe tim mạch.', 250000, 30, 'images/frontend/appointment-image.jpg'],
            ['sieu-am-tim', 'Siêu âm tim', 'tim-mach', 'Siêu âm đánh giá cấu trúc và chức năng tim.', 420000, 35, 'images/frontend/news-image1.jpg'],
            ['kham-nhi-tong-quat', 'Khám nhi tổng quát', 'nhi-khoa', 'Khám tổng quát và tư vấn chăm sóc sức khỏe trẻ em.', 200000, 30, 'images/frontend/news-image2.jpg'],
            ['tu-van-tiem-chung', 'Tư vấn tiêm chủng', 'nhi-khoa', 'Tư vấn lịch tiêm và theo dõi phản ứng sau tiêm.', 180000, 25, 'images/frontend/news-image3.jpg'],
            ['kham-da-lieu', 'Khám da liễu', 'da-lieu', 'Khám và điều trị mụn, viêm da, dị ứng da.', 180000, 25, 'images/frontend/news-image3.jpg'],
            ['noi-soi-tai-mui-hong', 'Nội soi tai mũi họng', 'tai-mui-hong', 'Nội soi hỗ trợ chẩn đoán viêm xoang, viêm họng và bệnh lý tai.', 320000, 30, 'images/frontend/news-image.jpg'],
            ['kham-mat-tong-quat', 'Khám mắt tổng quát', 'mat', 'Đo thị lực, khám bán phần trước và tư vấn chăm sóc mắt.', 220000, 30, 'images/frontend/news-image1.jpg'],
            ['cao-voi-rang', 'Cạo vôi răng', 'rang-ham-mat', 'Làm sạch mảng bám, đánh bóng răng và tư vấn vệ sinh răng miệng.', 300000, 40, 'images/frontend/news-image2.jpg'],
            ['kham-thai-dinh-ky', 'Khám thai định kỳ', 'san-phu-khoa', 'Theo dõi thai kỳ, siêu âm và tư vấn chăm sóc mẹ bầu.', 350000, 35, 'images/frontend/appointment-image.jpg'],
            ['do-mat-do-xuong', 'Đo mật độ xương', 'co-xuong-khop', 'Đánh giá nguy cơ loãng xương và tư vấn dự phòng gãy xương.', 450000, 30, 'images/frontend/about-bg.jpg'],
            ['dien-nao-do', 'Điện não đồ', 'than-kinh', 'Ghi nhận hoạt động điện não hỗ trợ chẩn đoán thần kinh.', 500000, 45, 'images/frontend/slider2.jpg'],
            ['tam-soat-tieu-duong', 'Tầm soát tiểu đường', 'noi-tiet', 'Đánh giá đường huyết, HbA1c và nguy cơ biến chứng.', 380000, 30, 'images/frontend/slider3.jpg'],
            ['noi-soi-tieu-hoa', 'Nội soi tiêu hóa', 'tieu-hoa', 'Nội soi dạ dày hoặc đại tràng theo chỉ định chuyên môn.', 850000, 45, 'images/frontend/news-image1.jpg'],
            ['do-chuc-nang-ho-hap', 'Đo chức năng hô hấp', 'ho-hap', 'Đánh giá chức năng phổi cho người bệnh hô hấp mạn tính.', 360000, 30, 'images/frontend/news-image2.jpg'],
            ['sieu-am-than-tiet-nieu', 'Siêu âm thận tiết niệu', 'than-tiet-nieu', 'Siêu âm đánh giá thận, bàng quang và đường tiết niệu.', 300000, 25, 'images/frontend/news-image3.jpg'],
            ['tam-soat-ung-thu', 'Tầm soát ung thư', 'ung-buou', 'Tư vấn gói tầm soát phù hợp theo tuổi, giới và yếu tố nguy cơ.', 1200000, 60, 'images/frontend/about-bg.jpg'],
            ['vat-ly-tri-lieu', 'Vật lý trị liệu', 'phuc-hoi-chuc-nang', 'Vật lý trị liệu giảm đau và phục hồi vận động.', 280000, 45, 'images/frontend/appointment-image.jpg'],
            ['cap-cuu-ban-dau', 'Cấp cứu ban đầu', 'cap-cuu', 'Tiếp nhận và xử trí cấp cứu ban đầu theo phân loại.', 500000, 30, 'images/frontend/slider1.jpg'],
            ['tu-van-dinh-duong', 'Tư vấn dinh dưỡng', 'dinh-duong', 'Xây dựng chế độ ăn cá nhân hóa theo tình trạng sức khỏe.', 220000, 35, 'images/frontend/news-image.jpg'],
            ['tu-van-tam-ly', 'Tư vấn tâm lý', 'tam-ly-lam-sang', 'Tư vấn stress, lo âu và cân bằng sức khỏe tinh thần.', 400000, 50, 'images/frontend/slider2.jpg'],
        ];

        $services = [];

        foreach ($records as [$slug, $name, $departmentSlug, $description, $price, $duration, $image]) {
            $services[$slug] = Service::updateOrCreate(
                ['slug' => $slug],
                [
                    'department_id' => $departments[$departmentSlug]->id,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'duration_minutes' => $duration,
                    'image' => $image,
                    'is_active' => true,
                ]
            );
        }

        return $services;
    }

    private function seedAppointments(array $users, array $doctors, array $schedules, array $services): array
    {
        $patients = array_values(array_filter($users, fn (User $user): bool => $user->role === 'patient'));
        $servicesList = array_values($services);
        $doctorById = [];

        foreach ($doctors as $doctor) {
            $doctorById[$doctor->id] = $doctor;
        }

        $reasons = [
            'Đau tức ngực khi vận động mạnh.',
            'Trẻ ho kéo dài và ăn kém.',
            'Kiểm tra đường huyết định kỳ.',
            'Theo dõi thai kỳ và siêu âm.',
            'Mắt mỏi khi làm việc với máy tính.',
            'Đau răng và chảy máu chân răng.',
            'Đau dạ dày sau bữa ăn.',
            'Khó thở khi thay đổi thời tiết.',
            'Đau khớp gối khi leo cầu thang.',
            'Tư vấn chế độ ăn giảm cân.',
            'Mất ngủ kéo dài trong hai tuần.',
            'Khám tổng quát trước khi đi công tác.',
            'Nổi mẩn đỏ và ngứa da.',
            'Ù tai và nghẹt mũi.',
            'Đau lưng sau chấn thương nhẹ.',
            'Kiểm tra chức năng thận.',
            'Tư vấn tầm soát ung thư theo độ tuổi.',
            'Cần phục hồi vận động sau bong gân.',
            'Sốt cao cần đánh giá ban đầu.',
            'Căng thẳng, khó tập trung trong công việc.',
        ];

        $statuses = ['confirmed', 'pending', 'completed', 'cancelled'];
        $appointments = [];

        for ($index = 0; $index < 20; $index++) {
            $patient = $patients[$index % count($patients)];
            $schedule = $schedules[$index % count($schedules)];
            $doctor = $doctorById[$schedule->doctor_id];
            $service = $servicesList[$index % count($servicesList)];
            $appointmentDate = $index === 0
                ? Carbon::tomorrow()
                : Carbon::today()->addDays($index - 8);
            $appointmentTime = $index === 0
                ? '08:30'
                : ($index % 2 === 0 ? '09:00' : '14:00');

            $appointments[] = Appointment::updateOrCreate(
                [
                    'patient_email' => $index === 0 ? 'patient@hospital.test' : $patient->email,
                    'appointment_date' => $appointmentDate->toDateString(),
                    'appointment_time' => $appointmentTime,
                ],
                [
                    'user_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'doctor_schedule_id' => $schedule->id,
                    'service_id' => $service->id,
                    'patient_name' => $patient->name,
                    'patient_phone' => $patient->phone,
                    'reason' => $reasons[$index],
                    'status' => $statuses[$index % count($statuses)],
                    'note' => $index % 3 === 0 ? 'Bệnh nhân cần đến trước giờ hẹn 15 phút.' : null,
                ]
            );
        }

        return $appointments;
    }

    private function seedPayments(array $appointments): void
    {
        $methods = ['cash', 'vnpay', 'momo'];

        foreach ($appointments as $index => $appointment) {
            $method = $methods[$index % count($methods)];
            $paid = $appointment->status === 'completed' || ($method !== 'cash' && $appointment->status === 'confirmed');
            $totalAmount = (float) ($appointment->service?->price ?? 0);
            $depositAmount = Payment::depositAmountFor($totalAmount);

            Payment::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'user_id' => $appointment->user_id,
                    'method' => $method,
                    'status' => $paid ? 'paid' : ($method === 'cash' ? 'unpaid' : 'pending'),
                    'amount' => $depositAmount,
                    'total_amount' => $totalAmount,
                    'deposit_amount' => $depositAmount,
                    'currency' => 'VND',
                    'transaction_code' => $paid ? 'SEED-'.$appointment->id : null,
                    'gateway_order_id' => $method === 'vnpay'
                        ? now()->format('YmdHis').str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT).str_pad((string) $index, 4, '0', STR_PAD_LEFT)
                        : 'HOSPSEED'.$appointment->id,
                    'gateway_response' => $paid ? ['seeded' => true] : null,
                    'paid_at' => $paid ? now() : null,
                    'deposit_paid_at' => $paid ? now() : null,
                ]
            );
        }
    }

    private function seedMedicalRecords(array $appointments): void
    {
        $symptoms = [
            'Đau nhẹ khi vận động, chưa ghi nhận khó thở lúc nghỉ.',
            'Ho khan, sốt nhẹ, ăn uống giảm.',
            'Mệt mỏi, khát nước nhiều, cần theo dõi đường huyết.',
            'Thai kỳ ổn định, cần tiếp tục theo dõi định kỳ.',
            'Mỏi mắt, khô mắt khi làm việc lâu.',
            'Đau răng, viêm nướu nhẹ.',
            'Đau vùng thượng vị sau ăn.',
            'Khò khè khi trời lạnh.',
            'Đau khớp gối, hạn chế vận động nhẹ.',
            'Tăng cân nhanh, cần điều chỉnh khẩu phần.',
            'Khó ngủ, tỉnh giấc nhiều lần.',
            'Không ghi nhận triệu chứng cấp tính.',
            'Mẩn đỏ vùng cánh tay, ngứa nhiều về đêm.',
            'Nghẹt mũi, ù tai từng cơn.',
            'Đau lưng vùng thắt lưng.',
            'Tiểu buốt nhẹ, cần xét nghiệm thêm.',
            'Không có triệu chứng đặc hiệu, tư vấn tầm soát.',
            'Sưng nhẹ cổ chân sau bong gân.',
            'Sốt, đau đầu, cần theo dõi dấu hiệu sinh tồn.',
            'Căng thẳng, khó tập trung, ngủ không sâu.',
        ];

        foreach ($appointments as $index => $appointment) {
            MedicalRecord::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'user_id' => $appointment->user_id,
                    'doctor_id' => $appointment->doctor_id,
                    'examined_at' => Carbon::today()->subDays($index)->toDateString(),
                    'symptoms' => $symptoms[$index],
                    'diagnosis' => 'Chẩn đoán ban đầu phù hợp với triệu chứng, cần theo dõi thêm theo chỉ định.',
                    'treatment' => 'Tư vấn chăm sóc tại nhà, điều chỉnh sinh hoạt và tái khám khi có dấu hiệu bất thường.',
                    'prescription' => $index % 2 === 0 ? 'Đơn thuốc mẫu theo chỉ định bác sĩ, dùng đúng liều và đúng thời gian.' : 'Chưa kê đơn thuốc, ưu tiên theo dõi và xét nghiệm bổ sung.',
                    'note' => 'Hồ sơ mẫu phục vụ kiểm thử giao diện quản lý bệnh viện.',
                    'follow_up_date' => Carbon::today()->addDays(14 + $index)->toDateString(),
                ]
            );
        }
    }

    private function seedNews(User $admin): void
    {
        $records = [
            ['huong-dan-dat-lich-kham-online', 'Hướng dẫn đặt lịch khám trực tuyến', 'Các bước đặt lịch khám trực tuyến nhanh chóng và thuận tiện.', 'Người dùng chọn chuyên khoa, bác sĩ, ngày giờ khám và gửi yêu cầu đặt lịch trên hệ thống.', 'images/frontend/news-image1.jpg'],
            ['luu-y-khi-kham-tim-mach', 'Lưu ý khi khám tim mạch', 'Một số điều cần chuẩn bị trước khi đi khám tim mạch.', 'Bệnh nhân nên mang theo kết quả khám cũ, danh sách thuốc đang dùng và đến đúng giờ hẹn.', 'images/frontend/news-image2.jpg'],
            ['khi-nao-can-kham-nhi', 'Khi nào cần đưa trẻ đi khám nhi', 'Các dấu hiệu phụ huynh không nên bỏ qua khi trẻ sốt hoặc ho kéo dài.', 'Trẻ cần được thăm khám khi sốt cao, bú kém, thở nhanh hoặc triệu chứng kéo dài nhiều ngày.', 'images/frontend/news-image3.jpg'],
            ['cham-soc-da-mua-nang', 'Chăm sóc da trong mùa nắng', 'Gợi ý bảo vệ da, phòng dị ứng và hạn chế kích ứng.', 'Sử dụng kem chống nắng, uống đủ nước và khám da liễu khi có biểu hiện bất thường.', 'images/frontend/news-image.jpg'],
            ['kiem-tra-suc-khoe-dinh-ky', 'Vì sao nên kiểm tra sức khỏe định kỳ', 'Khám định kỳ giúp phát hiện sớm nguy cơ bệnh lý.', 'Khám sức khỏe định kỳ giúp người bệnh chủ động phòng ngừa và điều chỉnh lối sống.', 'images/frontend/about-bg.jpg'],
            ['dinh-duong-cho-nguoi-cao-huyet-ap', 'Dinh dưỡng cho người cao huyết áp', 'Các nguyên tắc ăn uống hỗ trợ kiểm soát huyết áp.', 'Người bệnh nên giảm muối, tăng rau xanh và tuân thủ lịch tái khám.', 'images/frontend/news-image1.jpg'],
            ['phong-ngua-benh-ho-hap', 'Phòng ngừa bệnh hô hấp khi giao mùa', 'Các bước giảm nguy cơ viêm đường hô hấp.', 'Giữ ấm, vệ sinh tay và theo dõi triệu chứng ho sốt giúp phòng bệnh hiệu quả.', 'images/frontend/news-image2.jpg'],
            ['dau-da-day-khi-nao-can-noi-soi', 'Đau dạ dày khi nào cần nội soi', 'Những dấu hiệu cần khám tiêu hóa sớm.', 'Đau kéo dài, sụt cân hoặc nôn ra máu là dấu hiệu cần đi khám ngay.', 'images/frontend/news-image3.jpg'],
            ['tu-van-tiem-chung-cho-tre', 'Tư vấn tiêm chủng cho trẻ', 'Lịch tiêm phù hợp giúp bảo vệ trẻ trước bệnh truyền nhiễm.', 'Phụ huynh nên lưu lịch tiêm và thông báo tiền sử dị ứng trước khi tiêm.', 'images/frontend/news-image.jpg'],
            ['bao-ve-mat-khi-dung-may-tinh', 'Bảo vệ mắt khi dùng máy tính', 'Thói quen giúp giảm khô mắt và mỏi mắt.', 'Nghỉ mắt theo quy tắc 20-20-20 và khám mắt khi thị lực thay đổi.', 'images/frontend/news-image1.jpg'],
            ['cham-soc-rang-mieng-dung-cach', 'Chăm sóc răng miệng đúng cách', 'Đánh răng và lấy vôi răng định kỳ giúp phòng bệnh nha chu.', 'Nên khám răng miệng mỗi 6 tháng để phát hiện sớm sâu răng và viêm nướu.', 'images/frontend/news-image2.jpg'],
            ['theo-doi-thai-ky-an-toan', 'Theo dõi thai kỳ an toàn', 'Khám thai định kỳ giúp mẹ và bé được chăm sóc đúng giai đoạn.', 'Mẹ bầu cần lưu lịch khám, bổ sung dinh dưỡng và theo dõi dấu hiệu bất thường.', 'images/frontend/news-image3.jpg'],
            ['van-dong-phong-dau-khop', 'Vận động để phòng đau khớp', 'Tập luyện phù hợp giúp bảo vệ hệ cơ xương khớp.', 'Duy trì cân nặng hợp lý và tập luyện nhẹ nhàng giúp giảm nguy cơ đau khớp.', 'images/frontend/about-bg.jpg'],
            ['kiem-soat-duong-huyet-tai-nha', 'Kiểm soát đường huyết tại nhà', 'Người bệnh tiểu đường cần theo dõi đường huyết đúng cách.', 'Ghi nhận chỉ số đường huyết và tái khám định kỳ giúp kiểm soát bệnh tốt hơn.', 'images/frontend/slider2.jpg'],
            ['dau-dau-keo-dai-can-luu-y', 'Đau đầu kéo dài cần lưu ý gì', 'Một số trường hợp đau đầu cần khám chuyên khoa thần kinh.', 'Đau đầu dữ dội, kèm nôn ói hoặc yếu liệt cần được thăm khám sớm.', 'images/frontend/slider3.jpg'],
            ['soi-than-tiet-nieu-va-dau-hieu', 'Dấu hiệu sỏi thận tiết niệu', 'Đau hông lưng, tiểu buốt có thể liên quan bệnh tiết niệu.', 'Người bệnh nên uống đủ nước và khám khi có triệu chứng kéo dài.', 'images/frontend/news-image1.jpg'],
            ['tam-soat-ung-thu-theo-do-tuoi', 'Tầm soát ung thư theo độ tuổi', 'Tầm soát đúng thời điểm giúp phát hiện bệnh sớm.', 'Bác sĩ sẽ tư vấn gói tầm soát theo tuổi, giới và yếu tố nguy cơ cá nhân.', 'images/frontend/news-image2.jpg'],
            ['phuc-hoi-chuc-nang-sau-chan-thuong', 'Phục hồi chức năng sau chấn thương', 'Tập phục hồi đúng cách giúp người bệnh trở lại sinh hoạt an toàn.', 'Người bệnh cần tuân thủ bài tập và tránh vận động quá mức trong giai đoạn đầu.', 'images/frontend/news-image3.jpg'],
            ['so-cuu-khi-sot-cao', 'Sơ cứu khi sốt cao', 'Cách xử trí ban đầu khi người bệnh sốt cao.', 'Theo dõi nhiệt độ, bù nước và đưa người bệnh đi khám khi có dấu hiệu nguy hiểm.', 'images/frontend/news-image.jpg'],
            ['suc-khoe-tinh-than-noi-cong-so', 'Sức khỏe tinh thần nơi công sở', 'Căng thẳng kéo dài có thể ảnh hưởng giấc ngủ và hiệu suất làm việc.', 'Chủ động nghỉ ngơi, chia sẻ và tìm hỗ trợ chuyên môn khi stress kéo dài.', 'images/frontend/about-bg.jpg'],
        ];

        foreach ($records as $index => [$slug, $title, $excerpt, $content, $thumbnail]) {
            News::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $admin->id,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'thumbnail' => $thumbnail,
                    'status' => $index % 5 === 0 ? 'draft' : 'published',
                    'published_at' => $index % 5 === 0 ? null : now()->subDays($index),
                ]
            );
        }
    }

    private function seedContacts(): void
    {
        $records = [
            ['contact@example.com', 'Nguyễn Thúy An', '0911111111', 'Tư vấn đặt lịch', 'Tôi muốn được tư vấn cách đặt lịch khám.', 'new'],
            ['dangminhchau@example.com', 'Đặng Minh Châu', '0911111112', 'Hỏi lịch khám tim mạch', 'Tôi cần đặt lịch khám tim vào cuối tuần.', 'read'],
            ['hoanggiabao@example.com', 'Hoàng Gia Bảo', '0911111113', 'Tư vấn dịch vụ', 'Vui lòng tư vấn gói kiểm tra sức khỏe tổng quát.', 'replied'],
            ['vothanhhang@example.com', 'Võ Thanh Hằng', '0911111114', 'Khám nhi', 'Tôi muốn hỏi lịch khám cho bé 4 tuổi.', 'new'],
            ['phanducmanh@example.com', 'Phan Đức Mạnh', '0911111115', 'Khám cơ xương khớp', 'Tôi bị đau gối khi vận động, cần tư vấn chuyên khoa.', 'read'],
            ['maikhanhlinh@example.com', 'Mai Khánh Linh', '0911111116', 'Khám thai', 'Tôi cần đặt lịch khám thai định kỳ tuần này.', 'replied'],
            ['truonghainam@example.com', 'Trương Hải Nam', '0911111117', 'Tư vấn nội soi', 'Tôi muốn biết cần chuẩn bị gì trước khi nội soi.', 'new'],
            ['ngobaotram@example.com', 'Ngô Bảo Trâm', '0911111118', 'Khám da liễu', 'Tôi bị dị ứng da kéo dài và muốn đặt lịch khám.', 'read'],
            ['dinhnhatminh@example.com', 'Đinh Nhật Minh', '0911111119', 'Khám mắt', 'Tôi cần kiểm tra mắt do nhìn mờ khi làm việc.', 'new'],
            ['lythuha@example.com', 'Lý Thu Hà', '0911111120', 'Tư vấn dinh dưỡng', 'Tôi muốn được tư vấn chế độ ăn cho người cao huyết áp.', 'replied'],
            ['caominhquan@example.com', 'Cao Minh Quân', '0911111121', 'Khám hô hấp', 'Tôi ho kéo dài sau khi khỏi cảm cúm.', 'read'],
            ['hongocdiep@example.com', 'Hồ Ngọc Diệp', '0911111122', 'Tư vấn tâm lý', 'Tôi cần tư vấn về mất ngủ và căng thẳng.', 'new'],
            ['taphuonguyen@example.com', 'Tạ Phương Uyên', '0911111123', 'Khám răng hàm mặt', 'Tôi muốn đặt lịch cạo vôi răng.', 'read'],
            ['leminhthu@example.com', 'Lê Minh Thư', '0911111124', 'Khám nội tiết', 'Tôi cần kiểm tra tuyến giáp.', 'replied'],
            ['phamquangvinh@example.com', 'Phạm Quang Vinh', '0911111125', 'Khám tiết niệu', 'Tôi bị tiểu buốt và muốn được tư vấn.', 'new'],
            ['buithanhtruc@example.com', 'Bùi Thanh Trúc', '0911111126', 'Tầm soát ung thư', 'Tôi muốn biết các gói tầm soát phù hợp cho nữ trên 35 tuổi.', 'read'],
            ['vominhduc@example.com', 'Võ Minh Đức', '0911111127', 'Phục hồi chức năng', 'Tôi cần tập phục hồi sau bong gân cổ chân.', 'new'],
            ['nguyenkimchi@example.com', 'Nguyễn Kim Chi', '0911111128', 'Cấp cứu', 'Tôi muốn biết quy trình tiếp nhận cấp cứu.', 'replied'],
            ['tranbaongoc@example.com', 'Trần Bảo Ngọc', '0911111129', 'Khám tổng quát', 'Tôi cần tư vấn gói khám tổng quát cho gia đình.', 'new'],
            ['dohongphuc@example.com', 'Đỗ Hồng Phúc', '0911111130', 'Khám thần kinh', 'Tôi thường xuyên đau đầu và chóng mặt.', 'read'],
        ];

        foreach ($records as [$email, $name, $phone, $subject, $message, $status]) {
            Contact::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => $status,
                ]
            );
        }
    }

    private function seedBanners(): void
    {
        $records = [
            ['/dat-lich-kham', 'Đặt lịch khám trực tuyến', 'Chọn bác sĩ, chọn giờ khám và nhận xác nhận nhanh chóng.', 'images/frontend/slider1.jpg'],
            ['/bac-si', 'Đội ngũ bác sĩ chuyên khoa', 'Tra cứu thông tin bác sĩ và chuyên môn phù hợp.', 'images/frontend/team-image1.jpg'],
            ['/dich-vu', 'Dịch vụ y tế toàn diện', 'Tìm dịch vụ theo chuyên khoa, thời lượng và chi phí dự kiến.', 'images/frontend/appointment-image.jpg'],
            ['/khoa/tim-mach', 'Chăm sóc sức khỏe tim mạch', 'Theo dõi huyết áp, mạch vành và các bệnh lý tim.', 'images/frontend/about-bg.jpg'],
            ['/khoa/nhi-khoa', 'Chăm sóc sức khỏe trẻ em', 'Khám nhi tổng quát và tư vấn tiêm chủng.', 'images/frontend/slider2.jpg'],
            ['/khoa/da-lieu', 'Điều trị da liễu an toàn', 'Khám mụn, viêm da, dị ứng và chăm sóc da.', 'images/frontend/slider3.jpg'],
            ['/khoa/san-phu-khoa', 'Đồng hành cùng thai kỳ', 'Khám thai định kỳ và tư vấn sức khỏe sinh sản.', 'images/frontend/news-image3.jpg'],
            ['/khoa/mat', 'Bảo vệ thị lực mỗi ngày', 'Khám mắt, đo thị lực và tư vấn chăm sóc mắt.', 'images/frontend/news-image1.jpg'],
            ['/khoa/rang-ham-mat', 'Nụ cười khỏe mạnh', 'Chăm sóc răng miệng và nha chu định kỳ.', 'images/frontend/news-image2.jpg'],
            ['/khoa/tieu-hoa', 'Chăm sóc hệ tiêu hóa', 'Khám dạ dày, gan mật và nội soi khi cần.', 'images/frontend/news-image.jpg'],
            ['/khoa/ho-hap', 'Theo dõi bệnh hô hấp', 'Khám hen, viêm phế quản và khó thở kéo dài.', 'images/frontend/appointment-image.jpg'],
            ['/khoa/noi-tiet', 'Kiểm soát tiểu đường', 'Theo dõi đường huyết và bệnh lý tuyến giáp.', 'images/frontend/about-bg.jpg'],
            ['/khoa/co-xuong-khop', 'Vận động khỏe mạnh', 'Khám đau khớp, thoái hóa và chấn thương nhẹ.', 'images/frontend/slider1.jpg'],
            ['/khoa/than-kinh', 'Chăm sóc hệ thần kinh', 'Tư vấn đau đầu, chóng mặt và rối loạn giấc ngủ.', 'images/frontend/slider2.jpg'],
            ['/khoa/than-tiet-nieu', 'Sức khỏe thận tiết niệu', 'Khám thận, bàng quang và đường tiết niệu.', 'images/frontend/news-image1.jpg'],
            ['/khoa/ung-buou', 'Tầm soát ung thư sớm', 'Chủ động phát hiện nguy cơ theo độ tuổi.', 'images/frontend/news-image2.jpg'],
            ['/khoa/phuc-hoi-chuc-nang', 'Phục hồi vận động', 'Vật lý trị liệu và phục hồi sau chấn thương.', 'images/frontend/news-image3.jpg'],
            ['/khoa/dinh-duong', 'Dinh dưỡng cá nhân hóa', 'Tư vấn chế độ ăn phù hợp với tình trạng sức khỏe.', 'images/frontend/news-image.jpg'],
            ['/khoa/tam-ly-lam-sang', 'Chăm sóc sức khỏe tinh thần', 'Tư vấn stress, mất ngủ và cân bằng cảm xúc.', 'images/frontend/about-bg.jpg'],
            ['/tin-tuc', 'Cập nhật kiến thức sức khỏe', 'Theo dõi tin tức và hướng dẫn chăm sóc sức khỏe.', 'images/frontend/slider3.jpg'],
        ];

        foreach ($records as $index => [$link, $title, $subtitle, $image]) {
            Banner::updateOrCreate(
                ['link' => $link],
                [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'image' => $image,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'starts_at' => now()->subDays(7),
                    'ends_at' => now()->addMonths(6),
                ]
            );
        }
    }
}
