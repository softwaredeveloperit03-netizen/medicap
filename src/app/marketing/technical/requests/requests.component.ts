import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-requests',
  templateUrl: './requests.component.html',
  styleUrls: ['./requests.component.css']
})
export class RequestsComponent implements OnInit {

  clientlist;
  document_name;
  documents = [];

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getclientlist();
  }

  addDocument(data) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    this.documents.push(temp);
     data.reset();
  }



  deleteDocument(index) {
    this.documents.splice(index, 1);
  }

  getclientlist() {
    this.service.get('common.php?type=getClients').subscribe((response: any) => {
      this.clientlist = response;
    });
  }

  
   saveForm(data) {
      if (!data.valid) {
        alert('Please select client and add at least one document.');
        return;
      }
      if (!this.documents || this.documents.length === 0) {
        alert('Please add at least one document before submitting.');
        return;
      }
      const temp = data.value;
      temp['documents'] = this.documents;
      this.service.post('marketing/document.php?type=saveRequest', JSON.stringify(temp)).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.documents = [];
          alert('Document request sent to QA Head successfully');
          this.router.navigate(['/marketing/technical/log']);
        } else {
          alert(result.message || 'An error has occurred, please try again');
        }
      });
    }
}
