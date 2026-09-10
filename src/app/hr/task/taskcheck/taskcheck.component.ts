import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-taskcheck',
  templateUrl: './taskcheck.component.html',
  styleUrls: ['./taskcheck.component.css']
})
export class TaskcheckComponent implements OnInit {

  
  isView=false;
  results;
  employeeList;
  selectedReport = [];
  selectedFile:File;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTasks();
    this.getEmployees();
  }

  getTasks(){
    this.service.get('hr/task.php?type=TodaysTaskscheck').subscribe((response: any)=> {
      this.results = response;
    });
  }

  onFileChanged8(event) {
    this.selectedFile = event.target.files[0];
  }

  view(index){
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }

  approveTask(){
    const uploadData = new FormData();

 

    if (this.selectedFile !== undefined) {
      uploadData.append('task', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('hr/task.php?type=check_task&id='+this.selectedReport['id'],uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success(this.service.t('common.savedSuccess'));       
        this.getTasks();
        this.isView=false;
      } else {
        alertify.error('An error occured');
      }
    });
  }
}
