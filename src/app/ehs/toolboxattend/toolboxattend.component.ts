import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-toolboxattend',
  templateUrl: './toolboxattend.component.html',
  styleUrls: ['./toolboxattend.component.css'],
  providers: [DatePipe]
})
export class ToolboxattendComponent implements OnInit {

  toolbox;
  date;
  AllAttenArr;
  attendence;
  selectedIndex = [];
  isNew = false;
  
  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) { 
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getAttendanceToday();
    this.getAttendanceAll();
    this.getEmployee();
  }

    getAttendanceToday(){
      this.service.get('ehs/attendencetoolboxtalk.php?type=getAttendanceToday').subscribe(response => {
        this.toolbox = response
      });
    }
  
    getAttendanceAll(){
      this.service.get('ehs/attendencetoolboxtalk.php?type=getAttendanceAll').subscribe(response => {
        this.toolbox = response
      });
    }

    empName;
    emp_code;
    getEmployee(){
      this.service.get('ehs/attendencetoolboxtalk.php?type=getEmployee').subscribe(response => {
        this.empName = response
      });
    }

    employee;
    getDate(i){
      this.selectedIndex=this.employee[i-1];
      this.selectedIndex = this.emp_code;
    }

    saveAttendence(data){
      let temp = data.value;
      console.log(temp);
      this.service.post('ehs/attendencetoolboxtalk.php?type=saveAttendence',JSON.stringify(temp)).subscribe(response => {
        if(response['status'] === 'success')
        {
          alertify.success('Record Saved Successfully');
          this.isNew = false;
          this.getAttendanceAll();
        }
        else{
          alertify.error(response['status']);
        }
      });
    }
}
