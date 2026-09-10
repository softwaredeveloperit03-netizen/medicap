import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-registration-init',
  templateUrl: './registration-init.component.html',
  styleUrls: ['./registration-init.component.css']
})
export class RegistrationInitComponent implements OnInit {

  isView=false
    flag1=false
    flag3=false
  constructor(private service: DataAccessService, private router: Router) { }
  productList:any
  clientList:any
  registerIniationList=[]
  ngOnInit(): void {
    this.getProdcutList()
    this.getClientsLsit()
  }


  getProdcutList()
  {
    this.service.get('Dossier/Dossier.php?type=getProductsForRegistrationInitation').subscribe(response=>{
      this.productList = response;
        
    })
  }

  getClientsLsit(){
    this.service.get('Dossier/Dossier.php?type=getClientsForRegistrationInitation').subscribe(response=>{
      this.clientList = response;
    })
  }
  addData(data)
  {
  this.registerIniationList.push(data.value)  

  }

  saveRegisterInitation()
  {
    let temp={}
    temp['registerInitation']=this.registerIniationList;
    this.service.post('Dossier/Dossier.php?type=saveRegisterInitation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        alert('Record Inserted Successfully');
            this.router.navigate(['/regulatory-new']);

      } else {
        alert('Failed: An error occured, please try again!');
      }
    })


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
        this.flag3=true
  
      }
      if(this.flag1==false)
        {
        this.flag3=false
        }
  
  }
  

}
