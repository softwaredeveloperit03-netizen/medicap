import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-registration-status',
  templateUrl: './registration-status.component.html',
  styleUrls: ['./registration-status.component.css']
})
export class RegistrationStatusComponent implements OnInit {
  results:any
  isView=false
  isNew=false
  selectedResults=[]
    productList:any
    uploadCertificate:File
    current_status:any
    updateInfo=[]
    flag1=false
    flag2=false
    updateInfoView=false

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getRegisterIniationList()
    this.getProdcutList()
  }

    
  getRegisterIniationList()
  {
    this.service.get('Dossier/Dossier.php?type=getRegistraationStatusLog').subscribe(response=>{
      this.results = response;
      console.log(this.results['register_initatyion_list'])
        
    })
  }

  getProdcutList()
  {
    this.service.get('Dossier/Dossier.php?type=getProductsForRegistrationInitation').subscribe(response=>{
      this.productList = response;
        
    })
  }

  View(index)
  {
    this.selectedResults=this.results[index]

    this.updateInfoView=true
    this.isNew=false
    this.isView=true


  }
  onFileChange($event) {
    this.uploadCertificate = $event.target.files[0];
  }

  openNew(index)
  {
    this.selectedResults=this.results[index]

    this.isNew=true
    this.isView=true

  }
  closeNew()
  {
    this.isNew=false
    this.isView=false
    this.current_status=''
    this.updateInfo=[]

  }
  submit()
  {

    let  temp={}
    temp['current_status']=this.current_status
    temp['update_info']=this.updateInfo

    this.service.post('Dossier/Dossier.php?type=saveNewQueryClosingDetails&ID='+this.selectedResults['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Inserted  Successfully');
        this.isNew=false
        this.isView=false
        this.updateInfo=[]
        this.current_status=''

        this.Upload()   



      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }


  addData(data)
  {
    let temp=data.value
    temp['upload_certificate']=this.uploadCertificate.name
    this.updateInfo.push(temp)
    
  }
  onValueChange(newValue: string) {
    const regex = /^[0-9]+$/;     
    const isValidRegex = regex.test(newValue);
  
    if (isValidRegex) {

      this.flag1=false
    } else {


      this.flag1=true
      if(newValue==''|| null)
        {
          this.flag1=false
        }
    }
    if(this.flag1==true)
      {
        this.flag2=true

      }
      if(this.flag1==false)
        {
        this.flag2=false
        }

  }

  Upload()
  {
    const formData = new FormData();

   
   if (this.uploadCertificate !== undefined && this.uploadCertificate !== null) {
     formData.append('uploadCertificate', this.uploadCertificate, this.uploadCertificate.name);
   }

   this.service.post('Dossier/Dossier.php?type=saveUploadCertificateFile', formData).subscribe(response => {
    if (response['status'] == 'success') {
      alert('Record Updated  Successfully');

    } else {
      alert('Failed: An error occured, please try again!');
    }
  })

  }

   delRecord(index) {
    this.updateInfo.splice(index, 1);
  }


  closeView()
  {
    this.updateInfoView=false
    this.isNew=false
    this.isView=false
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Registration/' + url;
    window.open(url, '_blank');
  }

  }


