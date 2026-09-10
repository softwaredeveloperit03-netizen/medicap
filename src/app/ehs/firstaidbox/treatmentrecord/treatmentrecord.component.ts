import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-treatmentrecord',
  templateUrl: './treatmentrecord.component.html',
  styleUrls: ['./treatmentrecord.component.css'],
  providers: [DatePipe]
})
export class TreatmentrecordComponent implements OnInit {

  date;
  record;
  isNew = false;
  treatmentRecArr;

  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
    // this.getRecord();
    this.GET_InvolvedPersons();
  }

  // getRecord(){
  //   this.service.get('common.php?type=getRecord').subscribe( response => {
  //       this.record = response
  //   });
  // }
  
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


  saveRecord(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/statuscard.php?type=saveTreatmentRecord', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }
  
}
