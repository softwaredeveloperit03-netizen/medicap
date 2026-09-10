import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-initiatecc',
  templateUrl: './initiatecc.component.html',
  styleUrls: ['./initiatecc.component.css']
})
export class InitiateccComponent implements OnInit {

 
  selectedFile:File;
  selectedDev;
  selectedResult=[];
  attributes=[
    {"attribute": "Process Performance", "status": "", "description": "", "responsibility": ""},
    {"attribute": "Product Quality", "status": "", "description": "", "responsibility": ""},
    {"attribute": "Product Outputs(Yeild)", "status": "", "description": "", "responsibility": ""},
    {"attribute": "Analytical results", "status": "", "description": "", "responsibility": ""},
    {"attribute": "Additional testing", "status": "", "description": "", "responsibility": ""},
    {"attribute": "safety", "status": "", "description": "", "responsibility": ""},
    {"attribute": "List of the department and positions required to be informed/trained on the proposed change(attach list if required)", "status": "", "description": "", "responsibility": ""},
  ];

  attributess = '';
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getCCDetails();

  }

  add(data) {
    let temp = data.value;
    let temp1:any = {};
    temp1["attribute"] = temp["attribute"];
    temp1["status"] = temp["status"];
    temp1["description"] = temp["description"];
    temp1["responsibility"] = temp["responsibility"];
    this.attributes[this.attributes.length ]= temp1;
    data.reset();
  }

  saveData(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    temp['actions']=this.attributes;
    this.service.post('qms/cctemporary.php?type=initiateCC',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.router.navigate(['/qms/cctemporary']);
        data.resetForm();
      }else{
        alertify.error('some error Occured!');
      }
    });

  }
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }

  addParticular(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('attachment', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('qms/cctemporary.php?type=uploadAttachment&cc_no='+this.selectedResult['cc_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getCCDetails();
        alertify.success('Attachment Uploaded Successfully');
        // this.isView=false;
        // this.getPendingAttachements();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }


  getCCDetails() {
    this.service.get('qms/cctemporary.php?type=getCCDetails&cc_no='+this.selectedResult['cc_no']).subscribe((response: any) => {
      this.selectedResult = response;
    });
  }


}
