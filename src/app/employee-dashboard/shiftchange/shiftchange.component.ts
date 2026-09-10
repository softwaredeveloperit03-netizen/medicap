import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-shiftchange',
  templateUrl: './shiftchange.component.html',
  styleUrls: ['./shiftchange.component.css']
})
export class ShiftchangeComponent implements OnInit {

 
  isView = false;
  isNew = false;
  results;
  selectedCheckList ;
  selectedChec ;
  shifts;
    constructor(private service:DataAccessService) { }
  
    
    ngOnInit(): void {
      this.getapprisals_log();
      this.getDesignation();
      this.getcurrentShift();
      this.getShiftList();
      this.shift_chnge_log();
    }
  
    current_shift;
    cur_shift_data;
    Shift_Id;
    weekly_off;
  getcurrentShift() {
      this.service.get('hr/shift.php?type=getcurrentShift').subscribe((response: any) => {
      this.cur_shift_data = response;
      this.current_shift = this.cur_shift_data[0]['shift_name'];
      this.Shift_Id = this.cur_shift_data[0]['Shift_Id'];
      this.weekly_off = this.cur_shift_data[0]['weekly_off'];
    });
  }

  getapprisals_log() {
      this.service.get('hr/shift.php?type=get_shift_change_record').subscribe((response: any) => {
      this.results = response;
    });
  }

     
  getShiftList(){
    this.service.get('hr/shift.php?type=getShiftList').subscribe(response=>{
      this.shifts=response;
    })
  }
  chnge_shifts;
  shift_chnge_log(){
    this.service.get('hr/shift.php?type=shift_chnge_log').subscribe(response=>{
      this.chnge_shifts=response;
    })
  }


    designations;
    getDesignation() {
        this.service.get('hr/appraisalchecklist.php?type=getDesignation').subscribe((response: any) => {
        this.designations = response;
      });
    }
  

    view(index)
    {
      this.isView =  true ;
      this.selectedChec = this.results[index];
  
    }

    selected_shift=[];
    addShift(index){
      this.selected_shift=this.shifts[index-1];
    }


    save(data){
      if(!data.valid){
        alertify.error('All feilds are required');
        return;
      }
      let temp = data.value;
      temp['selected_shift_id']=this.selected_shift['id']
      temp['selected_shift_weekly_off']=this.weekly_off;
      this.service.post('hr/shift.php?type=save_shift_change_request_New', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getapprisals_log()
          this.isNew = false;
          data.reset();
          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }
    
  }
  