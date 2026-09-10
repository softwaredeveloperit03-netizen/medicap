import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {
  clients;
  isView = false;
  results;
  selectedClient = [];

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getInprocessComplaints();
    this.getclientlist();
  }

  getInprocessComplaints() {
    this.service.get('marketing/complaint.php?type=getInprocessComplaints').subscribe(response=>{
    this.results = response;
    });
  }
  getclientlist() {
    this.service.get('marketing/po.php?type=getClients').subscribe((response:any) => {
    this.clients = response;
    });
  }
  view(index) {
    this.selectedClient = this.results[index];
    this.isView = true;
  }

  save(data) {
    this.service.post('marketing/complaint.php?type=updateComplaint&complaint_no=' + this.selectedClient['id'], JSON.stringify(data.value)).subscribe(response=>{
      if (response['status'] == 'success') {
        this.isView =false;
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });

}
}
