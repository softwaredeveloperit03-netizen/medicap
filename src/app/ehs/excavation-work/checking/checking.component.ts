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
      { id: 1, condition: 'Check for underground pipe line', applicable:''},
      { id: 2, condition: 'Check for Underground electrical cable', applicable:''},
      { id: 3, condition: 'Explosive meter, test for solvent contents', applicable:''},
      { id: 4, condition: 'Provision of personal protective equipment', applicable:''},
      { id: 5, condition: 'Check for solvent presence and operation', applicable:''},
      { id: 6, condition: 'Area should be barricaded', applicable:''},
      { id: 7, condition: 'Heavy vehicles Movement should stopped', applicable:''},
      { id: 8, condition: 'Use Double lanyard Full Body Safety Harness', applicable:''},
      { id: 9, condition: 'Supervisor from executing agency/ contractor is available at work site', applicable:''},
      ];
    constructor(private service: DataAccessService,private datePipe: DatePipe) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

    ngOnInit() {
    this.getDepartments();
    this.getEntrys();
    }
    detailsWorkList=[]
    parsedPrecautions: { data: string; status: string }[] = [];

    view(index) {
      this.selectedBatch = this.entrys[index];
//      if (typeof this.selectedBatch['details_work'] === 'string') {
//   this.detailsWorkList = JSON.parse(this.selectedBatch['details_work']);
// } else {
  this.detailsWorkList = this.selectedBatch['precautions'];
// }


      this.isView = true;
    }
    change(index){
      let con = this.conditions[index];
      console.log(this.applicable)
    con['applicable'] = con['applicable'];
    this.conditions[index] = con;
    // console.log(this.conditions)
    }
    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    getEntrys(){
      this.service.get('ehs/excavation.php?type=getPendingExcavation').subscribe(response=>{
        this.entrys = response;
      })
    }


    updateHotWork(data,status){
      let temp=data.value;
      temp['conditions']=this.conditions;
       this.service.post('ehs/excavation.php?type=checkExcavation&status=' + status+'&id='+this.selectedBatch['id'],JSON.stringify(temp)).subscribe(response => {
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
