import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-perfchkrecord',
  templateUrl: './perfchkrecord.component.html',
  styleUrls: ['./perfchkrecord.component.css'],
  providers: [DatePipe]
})
export class PerfchkrecordComponent implements OnInit {

  //record;
  perfcheck;
  date;
  // isNew = false;
  selectedData = [];

  constructor(
    private service: DataAccessService, 
    private router: Router, 
    private datePipe: DatePipe
  ) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.getDailyPerformanceChk();
    this.GET_InvolvedPersons();
  }

  perfChk;
  getDailyPerformanceChk(){
    this.service.get('ehs/electronicWeightingBalance/eccentricitycheck.php?type=getDailyPerformance')
    .subscribe(response =>{
      this.perfChk = response;
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
 
    isNew;
    saveRecord(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/electronicWeightingBalance/eccentricitycheck.php?type=saveDailyPerformance', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }
  

}
