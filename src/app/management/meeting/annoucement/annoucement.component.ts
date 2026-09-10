import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-annoucement',
  templateUrl: './annoucement.component.html',
  styleUrls: ['./annoucement.component.css']
})
export class AnnoucementComponent implements OnInit {

  departments;
  employees;
  
  selectedEmp = [];

  managers = [];

  agenda = '';
  detail = '';
  agendas = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDepartments();
    this.getManagers();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getManagers() {
    this.service.get('management/meeting.php?type=getManagers').subscribe(response => {
      this.employees = response;
    });
  }

  selectManager(index) {
    index = index - 1;
    this.selectedEmp = this.employees[index];
    this.selectedEmp['index'] = index;
  }

  addManager() {
    this.managers[this.managers.length] = this.selectedEmp;
    this.employees.splice(this.selectedEmp['index'], 1);
  }

  addAgenda() {
    let temp = {};
    temp['agenda'] = this.agenda;
    temp['detail'] = this.detail;
    this.agendas[this.agendas.length] = temp;
    this.agenda = '';
    this.detail = '';
  }

  saveManagementMeetingAnnoucement(data) {
    let temp = data.value;
    temp['managers'] = this.managers;
    temp['agendas'] = this.agendas;
    this.service.post('management/meeting.php?type=saveAgenda', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Meeting Saved Successfully');
        this.router.navigate(['/management/meeting']);
      }
    });
  }

}
