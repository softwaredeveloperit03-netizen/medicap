import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router'
declare let alertify;
@Component({
  selector: 'app-accidentreport',
  templateUrl: './accidentreport.component.html',
  styleUrls: ['./accidentreport.component.css'],
  providers:[DatePipe]
})
export class AccidentreportComponent implements OnInit {
    date;
    departments;
    isNew=false;
    constructor(private service: DataAccessService,private datePipe: DatePipe,private router : Router) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

    ngOnInit() {
      this.GetSaveAccident();
      }

      NewaTT(){
        this.isNew=true;
        this.GET_InvolvedPersons();

      }
      dates;
  TodaysAttdence;
  AllAttdence;
  Accidents;
    GetSaveAccident(){
      this.service.get('ehs/deviation.php?type=GetSaveAccident').subscribe(response=>{
        this.Accidents = response;
      })
    }

selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }
    VictimList=[];
    WitnessList=[];
    addAtt(data){
      let temp=data.value;
      this.VictimList[this.VictimList.length]=temp;
      data.resetForm();
    }
    addWitness(data){
      let temp=data.value;
      this.WitnessList[this.WitnessList.length]=temp;
      data.resetForm();
    }



  InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }
    saveData(data){
      let temp = data.value;

    }



selectedTypes: string[] = [];
    onCheckboxChange(event: any) {
  const value = event.target.value;

  if (event.target.checked) {
    // ✅ Add to array if checked
    this.selectedTypes.push(value);
  } else {
    // ❌ Remove from array if unchecked
    this.selectedTypes = this.selectedTypes.filter(v => v !== value);
  }
}


submit(data){
  let temp=data.value;
  temp['selectedTypes']=this.selectedTypes;
  temp['VictimList']=this.VictimList;
  temp['WitnessList']=this.WitnessList;

 this.service.post('ehs/deviation.php?type=SaveAccident', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
          this.GetSaveAccident();
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
}
isView=false;
selectedResult=[];
View(index){
  this.selectedResult=this.Accidents[index]
  this.isView=true;
}
  }
