import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-statuscard',
  templateUrl: './statuscard.component.html',
  styleUrls: ['./statuscard.component.css'],
  providers: [DatePipe]
})
export class StatuscardComponent implements OnInit {

  // status;
  // selectedIndex = [];
  date;

  constructor(
    private service: DataAccessService, 
    private router: Router, 
    private datePipe: DatePipe
  ) { 
    this.date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    // this.getStatusCard();
    //this.getEmployeeQualityControl();
    // this.getFrequency();
    this.getDepartments();
    this.getSection();
     this.GET_InvolvedPersons();
  }

   departments;
   getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

    // getStatusCard(){
    //   this.status.get('ehs/statuscard.php?type=getStatusCard').subscribe(response => {
    //     this.status = response
    //   });
    // }

    // frequency;
    // getFrequency(){
    //   this.service.get('master/frequency.php?type=getFrequency').subscribe(response => {
    //     this.frequency = response;
    //   })
    // }

    Sections;

  getSection() {
    this.service
      .get('master/section.php?type=getSection')
      .subscribe((response) => {
        this.Sections = response;
      });
  }

   selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }
    InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }

  //  emps;
  //  getEmployeeQualityControl() {
  //   this.service.get('deptEmployee.php?type=getEmployeeQualityControl')
  //     .subscribe((response) => {
  //       this.emps = response;
  //     });
  // }

  // getData(i){
  //   this.selectedIndex = this.Sections[i-1];
  // }
   isNew;
  saveStatusCard(data){
  let temp = data.value;
    console.log(temp);
    this.service.post('ehs/statuscard.php?type=saveStatusCard', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Saved Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }
}
