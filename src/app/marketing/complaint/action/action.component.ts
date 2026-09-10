import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-action',
  templateUrl: './action.component.html',
  styleUrls: ['./action.component.css']
})
export class ActionComponent implements OnInit {
  isView = false;
  results: any[] = [];
  selectedClient: any = {};

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingComplaints();
  }

  getClientName(item: any): string {
    if (!item) {
      return 'NA';
    }
    const name = item.client_name || item.company || item.LglNm || item.TrdNm;
    if (name) {
      return item.client_code ? `${name} (${item.client_code})` : name;
    }
    return item.client_code || 'NA';
  }

  getPendingComplaints() {
    this.service.get('marketing/complaint.php?type=getPendingComplaints').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    this.selectedClient = { ...this.results[index] };
    this.isView = true;
  }

  Save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('marketing/complaint.php?type=updateComplaint&complaint_no=' + this.selectedClient['id'],JSON.stringify(data.value)).subscribe(response =>{
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPendingComplaints();
        this.isView = false;
        alert("submited succesfully");
      } else{
        alert('Failed: An error occured, Please try again!');
      }
    });
  }
}
