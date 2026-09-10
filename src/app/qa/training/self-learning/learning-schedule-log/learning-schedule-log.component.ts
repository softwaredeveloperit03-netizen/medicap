import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-learning-schedule-log',
  templateUrl: './learning-schedule-log.component.html',
  styleUrls: ['./learning-schedule-log.component.css']
})
export class LearningScheduleLogComponent implements OnInit {

  isView = false;
  IncomingObjOfEmployee = {
    id: 1,
    entry_date: new Date(),
    department: 'IT',
    employee_name: 'John Doe',
    topic: 'Angular Basics',
    title: 'Introduction to Angular',
    duration: '2 hours',
    expected_output: 'Understanding the basics of Angular',
    schedule_by: 'Jane Smith'
  };
  results: any[] = [
    {
      id: 1,
      entry_date: new Date(),
      department: 'IT',
      employee_name: 'John Doe',
      topic: 'Angular Basics',
      title: 'Introduction to Angular',
      duration: '2 hours',
      expected_output: 'Understanding the basics of Angular',
      schedule_by: 'Jane Smith'
    },
    {
      id: 2,
      entry_date: new Date(),
      department: 'HR',
      employee_name: 'Alice Johnson',
      topic: 'Communication Skills',
      title: 'Effective Communication Strategies',
      duration: '1.5 hours',
      expected_output: 'Improving communication within the team',
      schedule_by: 'Bob Brown'
    },
    {
      id: 3,
      entry_date: new Date(),
      department: 'Finance',
      employee_name: 'Michael Jordan',
      topic: 'Financial Analysis',
      title: 'Introduction to Financial Planning',
      duration: '3 hours',
      expected_output: 'Understanding financial statements',
      schedule_by: 'Emily Davis'
    },
    {
      id: 4,
      entry_date: new Date(),
      department: 'Marketing',
      employee_name: 'Sarah Parker',
      topic: 'Digital Marketing',
      title: 'Social Media Marketing Strategies',
      duration: '2.5 hours',
      expected_output: 'Improving online presence',
      schedule_by: 'Chris Evans'
    },
    {
      id: 5,
      entry_date: new Date(),
      department: 'Operations',
      employee_name: 'Alex Rodriguez',
      topic: 'Supply Chain Management',
      title: 'Inventory Management Techniques',
      duration: '2 hours',
      expected_output: 'Optimizing inventory levels',
      schedule_by: 'Emma Thompson'
    }
  ];
  
  loading;
  departments;
  constructor(private dataAccessService:DataAccessService) { }

  ngOnInit(): void {
    this.isView = false;
    this.getDepartments();
    console.log('isView is activated',this.isView);
  }
  getDepartments() {
    this.dataAccessService.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  save(data) {
    console.log('learning new content master', data);

    if (!data.valid) {
      // alertify.error('All fields are required');
      return;
    }
    // this.dataAccessService.post('engineering/chilling.php?type=saveDaikinOperation',JSON.stringify (data.value)).subscribe(response =>{
    //   if (response['status'] === 'success') {
    //     alertify.success('Record Inserted successfully');
    //     data.resetForm();
    //   } else {
    //     alertify.error(response['status']);
    //   }
    // });
  }

}
