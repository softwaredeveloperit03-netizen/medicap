import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  complaints;
  isForm = false;

  documents=[
    {'document_review':'Batch Manufacturing Record','status': false},
    {'document_review':'Finished Product Testing Record','status': false},
    {'document_review':'Batch Packing Record','status': false},
    {'document_review':'In process Testing Records','status': false},
    {'document_review':'Active Ingredient (API)','status': false},
    {'document_review':'Packing Material Testing Report','status': false}
  ];


  constructor(private service: DataAccessService, private router:Router) {
   }

  ngOnInit() {
    this.getMarketComplaints();
  }

  getMarketComplaints() {
    this.service.get('qaDepartment.php?type=getMarketComplaints').subscribe(response => {
      this.complaints = response;
    });
  }
  selectedForm;
  primary_observation = '';
  viewForm(index){
    this.selectedForm = this.complaints[index];
    this.primary_observation = this.selectedForm?.primary_observation || '';
    this.documents = this.documents.map((d) => ({ ...d, status: false }));
    const selectedDocs = Array.isArray(this.selectedForm?.documents) ? this.selectedForm.documents : [];
    this.documents.forEach((d) => {
      d.status = selectedDocs.includes(d.document_review);
    });
    this.isForm = true;
  }
  savemarketComplaint(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.documents.length; i++) {
      let department = this.documents[i];
      if (department['status']) {
        test[test.length] = department['document_review'];
      }
    }
    temp['documents'] = test;
    temp['id'] = this.selectedForm?.id;
    this.service.post('qaDepartment.php?type=reviewMarketComplaint&id=' + this.selectedForm?.id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == "success") {
        data.resetForm();
        alert('Complaint reviewed and sent for approval successfully');
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
