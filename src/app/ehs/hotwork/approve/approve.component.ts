import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css'],
  providers:[DatePipe]

})
export class ApproveComponent implements OnInit {
    date;
    departments;

   form: any = {
    plant: '',
    location: '',
    date: '',
    validFrom: '',
    validTo: '',
    criticalJob: '',
    permissionTo: '',
    jobDescription: '',
    originDept: '',
    executingDept: '',
    hodEngineering: '',
    ehsDept: ''
  };
  plant=localStorage.getItem('')



    constructor(private service: DataAccessService,private datePipe: DatePipe,private router:Router) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

    ngOnInit() {

       this.Originating();
      }
      equipments;
      results;
  Originating(){

    this.service.get('ehs/EHS_hotwater.php?type=getHowaterToEHS&dept='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
      });
    }
    isView=false;
    selectedBatch=[]
    selectedCondition=[]
     view(index) {
      this.selectedBatch = this.results[index];
      this.selectedCondition=this.selectedBatch['conditions']
      this.isView = true;
    }

    saveData(data){
      let temp = {}


      console.log('temp :>> ', temp);
      this.service.post('ehs/EHS_hotwater.php?type=saveWorkEHSDEPT&id='+this.selectedBatch['id'],JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
         this.router.navigate(['/hrfordepthead'])
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
    }
  }
