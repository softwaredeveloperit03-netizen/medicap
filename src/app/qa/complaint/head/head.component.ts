import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-head',
  templateUrl: './head.component.html',
  styleUrls: ['./head.component.css']
})
export class HeadComponent implements OnInit {

  complaints;
  isForm = false;

  documents=[
    {'document_review':'Batch Manufacturing Record','status':'',},
    {'document_review':'Finished Product Testing Record','status':''},
    {'document_review':'Batch Packing Record','status':''},
    {'document_review':'In process Testing Records','status':''},
    {'document_review':'Active Ingredient (API)','status':''},
    {'document_review':'Packing Material Testing Report','status':''}
  ];


  constructor(private service: DataAccessService, private router:Router) {
   }

  ngOnInit() {
    this.getMarketComplaints();
  }

  getMarketComplaints() {
    this.service.get('qaDepartment.php?type=getMarketComplaints_approval').subscribe(response => {
      this.complaints = response;
    });
  }
  selectedForm;
  viewForm(index){
    this.selectedForm = this.complaints[index];
    this.isForm = true;
  }
  savemarketComplaint(data) {
    if (!this.selectedForm || !this.selectedForm.id) {
      alert('Invalid complaint selected');
      return;
    }
    const temp = data.value || {};
    temp['id'] = this.selectedForm.id;
    this.service.post('qaDepartment.php?type=approveMarketComplaint&id=' + this.selectedForm.id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == "success") {
        alert('Complaint approved successfully');
        this.getMarketComplaints();
        this.isForm = false;
      } else {
        alert('An error occured, please try again');
      }
    });
  }

  closeForm() {
    this.router.navigateByUrl('/qa/complaints/dashboard');
  }

  updateDept(value, i) {
    this.documents[i].status = value;
  }

}
