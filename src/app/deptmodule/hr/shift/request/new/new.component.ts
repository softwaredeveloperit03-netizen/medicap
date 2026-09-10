import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

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
      this.getShiftList();
      this.getDepartments();
      this.shift_chnge_log();
    }
    result;
    departments;
    getDepartments() {
      this.service.get('hr/employee.php?type=get_department_by_designation')
        .subscribe(response => {
          this.departments = response;
        });
    }
    getEmployees(value){
 
      this.service.get('hr/shift.php?type=getEmployees&department1='+value).subscribe(response=>{
        this.result=response;
      })
    }
  
    current_shift;
    cur_shift_data;
    Shift_Id;
    weekly_off;
  getcurrentShift(value) {
    this.current_shift='';
    this.Shift_Id='';
    this.weekly_off='';
      this.service.get('hr/shift.php?type=getcurrentShift&empid='+value).subscribe((response: any) => {
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
