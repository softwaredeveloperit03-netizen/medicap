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
      { id: 1, condition: 'Proper secure safe access/approach provided e.g. Ladder/Staircase', applicable:''},
      { id: 2, condition: 'Proper Scaffolding with secure foot hold provided (Above 15M height M.S. pipe Scaffolding mandatory)', applicable:''},
      { id: 3, condition: 'Roof ladders (crewing boards, duck ladder) have been provided', applicable:''},
      { id: 4, condition: 'Arrangement for securing /safe anchorage of roof ladders is made', applicable:''},
      { id: 5, condition: 'Safety belts provided with life line are in good condition Arrangement rage made (life line in case of Horizontal movement Secured to structure from both end of life line)', applicable:''},
      { id: 6, condition: 'Fall arrestors are provided in case of vertical/inclined roof sheet in Work', applicable:''},
      { id: 7, condition: 'Machinery below work area stopped/guarded', applicable:''},
      { id: 8, condition: 'All persons are properly trained/instructed before starting the work At height', applicable:''},
      { id: 9, condition: 'Supervisor from executing agency/ contractor is available at work site', applicable:''},
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
    // console.log(this.conditions)   
    }
    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    getEntrys(){
      this.service.get('ehs/workheight.php?type=getWorkHeight').subscribe(response=>{
        this.entrys = response;
      })
    }
  
    
    updateHotWork(data,status){
      let temp=data.value;
      temp['conditions']=this.conditions;
       this.service.post('ehs/workheight.php?type=checkWorkHeight&status=' + status+'&id='+this.selectedBatch['id'],JSON.stringify(temp)).subscribe(response => {
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
  