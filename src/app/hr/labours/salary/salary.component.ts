import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-salary',
  templateUrl: './salary.component.html',
  styleUrls: ['./salary.component.css']
})
export class SalaryComponent implements OnInit {

  labourlist = [];
  labourselected = [];
  labour_id = '';
  daily_wages = 0;
  laboursalary;
  list;
  downloadview = false;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getlabour();
    
  }
  getlabour() {
    this.service.get('hrDepartment.php?type=getlabourlist').subscribe((response:any) => {
      this.labourlist = response;
    });
  }
  selectedlabour(index){
    this.labour_id = this.labourlist[index].labour_id;
    this.daily_wages = this.labourlist[index].daily_wages;
  }
  monthdays= 0;
  present = 0;
  absent = 0;
  overtime = 0;
  totalearning = 0;


  calculate(data) {
    if(data.valid){
      let temp= data.value;
      temp['labour_no'] = temp.labour_no;
      temp['month'] = temp.month;
      this.service.post('hrDepartment.php?type=calculatelaboursalary', JSON.stringify(temp)).subscribe(response => {
        this.laboursalary = response;
        this.monthdays = response['monthdays']
        this.present = response['present'];
        this.absent = this.monthdays - this.present;
        this.totalearning =  response['totalearning'];
        this.overtime =  response['overtime'];
        // this.totalearning =  this.present * this.daily_wages;
        this.downloadview = true;
      });
    } else {
      alertify.error('Sellect All Feilds')
    }
  }
  generatepdf(salaryForm) {
    window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadlaboursalary&id='+this.labour_id+'&month='+salaryForm.value.month+'&token=' + localStorage.getItem('token'))
  }

}
