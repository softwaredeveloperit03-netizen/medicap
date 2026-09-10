import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router'
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]

})
export class NewComponent implements OnInit {
    date;
    departments;
    
    constructor(private service: DataAccessService,private datePipe: DatePipe,private router : Router) { 
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit() {
      this.getDepartments();
      }
  
    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    saveData(data){
      let temp = data.value;
      this.service.post('ehs/vesselEntry.php?type=saveVesselEntry', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
         this.router.navigate(['/ehs/entry-permit'])
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     }); 
    }
  }
  