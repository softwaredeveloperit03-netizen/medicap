import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;


@Component({
  selector: 'app-eccentricitychk',
  templateUrl: './eccentricitychk.component.html',
  styleUrls: ['./eccentricitychk.component.css']
})
export class EccentricitychkComponent implements OnInit {

  newRecord;
  // isNew = false;
  selectedBalCheck = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  /*
  getCheck(){
    this.check.get('common.php?type=getCheck').subscribe(response => {
      this.check = response
    })
  }
  */


   isNew;
    submitEccentChk(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/electronicWeightingBalance/eccentricitycheck.php?type=saveEccentricitychk', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }
 
}
