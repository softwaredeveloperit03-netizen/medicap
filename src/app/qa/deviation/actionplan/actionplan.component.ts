import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-actionplan',
  templateUrl: './actionplan.component.html',
  styleUrls: ['./actionplan.component.css']
})
export class ActionplanComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  isnot=false;
  remedialList=[];
  correctList=[];
  preventiveList=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingActionPlan();
  }

  getPendingActionPlan(){
    this.service.get('qms/deviation.php?type=getPendingActionPlan').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + '../../upload/deviation/' + link);
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  getcompletedStatus(value){
    if(value== 'Not Completed'){
      this.isnot=true;
    }else{
      this.isnot=false;
    }
  }

  addRemedial(data){
    this.remedialList[this.remedialList.length]=data.value;
    data.reset();
  }

  addcorrect(data){
    this.correctList[this.correctList.length]=data.value;
    data.reset();
  }

  addpreventive(data){
    this.preventiveList[this.preventiveList.length]=data.value;
    data.reset();
  }
  deleteprevent(index){
    this.preventiveList.splice(index,1);
  }

  deletecorrect(index){
    this.correctList.splice(index,1);
  }
  deleteremedial(index){
    this.remedialList.splice(index,1);
  }

  save(){
    let temp={};
    temp['correct_action']=this.correctList;
    temp['remidial_action']=this.remedialList;
    temp['prevent_action']=this.preventiveList;

    temp['deviation_no']=this.selectedResult['deviation_no'];
     this.service.post('qms/deviation.php?type=saveActionPlan&id=' +this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Save Successfully!');
        this.isView = false;
        this.getPendingActionPlan();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }



}
