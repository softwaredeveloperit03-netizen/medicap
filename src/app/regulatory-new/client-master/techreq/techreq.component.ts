import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-techreq',
  templateUrl: './techreq.component.html',
  styleUrls: ['./techreq.component.css']
})
export class TechreqComponent implements OnInit {

  
  clientlist;
  document_name;
  documents = [];

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getclientlist();
  }

  addDocument() {
    this.documents[this.documents.length] = this.document_name;
    this.document_name = '';
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
        alert('An error occured, please try again!');
        return;
      }
      let temp=data.value;
      temp['documents']=this.documents;
      this.service.post('marketing/document.php?type=saveRequest',JSON.stringify(temp)).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.documents = [];
          alert('Document Request Saved Successfully');
          this.router.navigate(['/technical']);
        } else {
          alert('An error has occurred, please try again');
        }
      });
    }
}
