import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isUser = false;
  isChecker = false;
  isApprover = false;

  isView = false;
  results;

  selectedClient = [];
  constructor(private service: DataAccessService, private router: Router) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit() {
    this.getclientlist();
  }

  getclientlist() {
    this.service.get('marketing/client.php?type=getClientsLog').subscribe((response: any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedClient = this.results[index];
    this.isView = true;
  }

  updateClient(status){
    this.service.get('marketing/client.php?type=updateClient&status=' + status + '&id=' + this.selectedClient['id']).subscribe(response => {
      if (response['status'] =='success') {
        alertify.success('Client Updated Successfully');
        this.isView = false;
        this.getclientlist();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  edit(id) {
    this.router.navigate(['/clients/edit/'+id]);
  }

}
