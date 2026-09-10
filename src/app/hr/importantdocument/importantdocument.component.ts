import { Component, OnInit } from '@angular/core';
import { ReactiveFormsModule,FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-importantdocument',
  templateUrl: './importantdocument.component.html',
  styleUrls: ['./importantdocument.component.css']
})
export class ImportantdocumentComponent implements OnInit {
  selected:[];
  documentlist;
  isClients = false;
  loading: any;
  indendForm: any;
  isview=false;
  d_type;
  docfile: File;

  constructor(private router: Router ,private service: DataAccessService) { }

  ngOnInit(): void {

    this.getDocuments();
  }
 

  onFileChanged8(event) {
    this.docfile = event.target.files[0];
  }

  viewChallan(url) {
 
    url = this.service.url + '../../upload/important_document/' + url;
    window.open(url, '_blank');
  }


  getDocuments() {
    this.service.get('hr/employee.php?type=get_importantDoc').subscribe(response => {
      this.documentlist = response;
    });
  }


  addForm(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    const temp = data.value;

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.docfile !== undefined) {
      uploadData.append('doc', this.docfile, this.docfile.name);
    }

    this.service.post('hr/employee.php?type=important_doc', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.getDocuments();
        this.isview=false;
        alertify.success('Document Added Successfully');
       } else {
        alertify.error(response['status']);
      }
    });

}

 
 






}
