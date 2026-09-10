import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css'],
  providers:[DatePipe]
})
export class CheckingComponent implements OnInit {
  

  date;
  departments;
  isView = false;
  entrys;
  applicable='';
  selectedBatch = [];
  conditions=[
    { id: 1, condition: 'Can work be carried out without use of welding /cutting', applicable:''},
    { id: 2, condition: 'Is connecting flammable /acid /caustic lines isolated/blinded.', applicable:''},
    { id: 3, condition: 'Is equipment /area cleaned drained/emptied and flushed with water /steam?', applicable:''},
    { id: 4, condition: 'There is no flammable/ combustible material nearby', applicable:''},
    { id: 5, condition: 'Is transfer/sampling/venting/centrifuging of solvent/flammables is stopped in nearby area', applicable:''},
    { id: 6, condition: 'Is nearby openings /drainage covered?', applicable:''},
    { id: 7, condition: 'Barricade the entire area below elevated work locations', applicable:''},
    { id: 8, condition: 'Is fire extinguisher readily available', applicable:''},
    { id: 9, condition: 'Is welding machine earthed to nearest point of job?', applicable:''},
    ];
  constructor(private service: DataAccessService,private datePipe: DatePipe) { 
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
  this.getDepartments();
  this.getEntrys();
  }
  view(index) {
    this.selectedBatch = this.entrys[index];
    this.isView = true;
  }

  change(index){
    let con = this.conditions[index];
    console.log(this.applicable)
  con['applicable'] = con['applicable']; 
  this.conditions[index] = con;
  console.log(this.conditions);  

  }
  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments = response;
    })
  }

  getEntrys(){
    this.service.get('ehs/hotwater.php?type=getPendingHotWater').subscribe(response=>{
      this.entrys = response;
    })
  }

  
  updateHotWork(data,status){
    let temp=data.value;
    temp['conditions']=this.conditions;
     this.service.post('ehs/hotwater.php?type=checkHotWater&status=' + status+'&id='+this.selectedBatch['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.applicable='';
        this.getEntrys();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
}
