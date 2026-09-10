import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-cleaningrec',
  templateUrl: './cleaningrec.component.html',
  styleUrls: ['./cleaningrec.component.css'],
})
export class CleaningrecComponent implements OnInit {

  cleaningstep;
  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void { 
    this.getcleaningstep();
  }
  
  getcleaningstep(){
    this.service.get('common.php?type=saveCleaningRecord').subscribe(response =>{
        this.cleaningstep =response
      });
  }

  // addcleaning(data){
  //   let temp = data.value;
  //   console.log(temp);
  //   this.service.post('',JSON.stringify(temp)).subscribe(response => {
  //    console.log('Form saved Successfully);
  // });
  // }

  submitCleaning(data)
  {
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/fire.php?type=saveCleaningRecord',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
          this.getcleaningstep();
        } else {
          alertify.error(response['status']);
        }
  });
  }
}
