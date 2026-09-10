import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-salary-calculation',
  templateUrl: './salary-calculation.component.html',
  styleUrls: ['./salary-calculation.component.css']
})
export class SalaryCalculationComponent implements OnInit {
  employeelist = [];
  employeeselected = [];
  bonus = 0;
  monthly = 0;
  attendance;
  list;

  monthlysalary = 0;
  monthdays = 0;
  presentdays = 0;
  absentdays = 0;
  paiddays = 0;
  perdaysalary = 0;
  totalearning = 0;
  downloadview= false;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getemployee();
    
  }
  getemployee() {
    this.service.get('hrDepartment.php?type=generatedappointment').subscribe((response:any) => {
      this.employeelist = response;
    });
  }
  calculate(salaryForm) {
    if(salaryForm.valid){
      const temp = new FormData;
      temp['emp_id'] = salaryForm.value.emp_id;
      temp['month'] = salaryForm.value.month;
      this.service.post('hrDepartment.php?type=calculateempsalary', JSON.stringify(temp)).subscribe(response => {
        this.monthlysalary = response['monthlysalary'];
        this.monthdays = response['monthdays'];
        this.presentdays = response['present'];
        
        this.absentdays = this.monthdays - this.presentdays;
        this.paiddays = this.presentdays;
        this.perdaysalary = parseFloat((this.monthlysalary / this.monthdays).toFixed(2));
        this.totalearning = parseFloat((this.perdaysalary * this.paiddays).toFixed(2));
        this.downloadview = true;
      });
    } else {
      alert('Sellect All Feilds')
    }
  }
  generatepdf(salaryForm) {
    window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadempsalary&id='+salaryForm.value.emp_id+'&month='+salaryForm.value.month+'&token=' + localStorage.getItem('token'))
  }


}
