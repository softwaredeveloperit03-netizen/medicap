import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-registration-query',
  templateUrl: './registration-query.component.html',
  styleUrls: ['./registration-query.component.css']
})
export class RegistrationQueryComponent implements OnInit {
  results:any
  isNew=false
  isView=false
  registrationQueryList=[]
  selectedResult=[]
  viewSelectedResult=[]
  updatedResult=[]
  updatedSelectedResult=[]
    Departments: any
    View=false
    isUpdate=false
    updatedView=false
    queryUpdatedResults: any
    queryResults=[]
    queryClosing=false
    queryClosingResults=[]
    registrationStatus:any
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getProdcutList()
    this.getDpartments()
  }

  
  getProdcutList()
  {
    this.service.get('Dossier/Dossier.php?type=getRegisterInitationRecords').subscribe(response=>{
      this.results = response;
      console.log(this.results['register_initatyion_list'])
        
    })
  }

  getDpartments()
  {
    this.service.get('Dossier/Dossier.php?type=getDepartmentsList').subscribe(response=>{
    this.Departments=response
        
    })
  }
  openRegistrationInitation(index)
  {
    this.isNew=true
    this.isView=true
    this.selectedResult=this.results[index]

  }

  save(data)
  {
    let temp={}
    this.registrationQueryList.push(data.value)
    temp['registrationQuery']=this.registrationQueryList;
    this.service.post('Dossier/Dossier.php?type=saveRegisterQuery&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        alert('Record Inserted Successfully');
        this.isView=false
        this.isNew=false
        this.View=false
        this.isUpdate=false
        this.getProdcutList()

      } else {
        alert('Failed: An error occured, please try again!');
      }
    })

  }

  closeNew()
  {

    this.isView=false
    this.isNew=false
  }
  view(index)
  {
    this.queryResults=this.results[index]
    this.getUpdatedView(this.queryResults['id'])
    //  this.viewSelectedResult=queryUpdatedResults
    this.isView=true
    this.isNew=false
    this.View=true
  }

  getUpdatedView(id)
  {
    this.service.get('Dossier/Dossier.php?type=getUpdatedView&ID='+id).subscribe(response=>{
    this.queryUpdatedResults=response
        
    })
  }

  closeView()
  {
    this.isView=false
    this.isNew=false
    this.View=false
  }
  openUpdateQuery(index)
  {
    this.updatedResult=this.results[index]
    console.log(this.updatedResult)

    this.isUpdate=true
    this.View=false
    this.isNew=false


  }

  saveUpdateQuery(data)
  {
    let tempVal={}
    let temp=data.value
    tempVal['admin_process']=temp['admin_process']
    tempVal['client_autherity']=temp['client_autherity']
    tempVal['department']=temp['department']
    tempVal['expected_time']=temp['expected_time']
    tempVal['receipt_date']=temp['receipt_date']
    tempVal['query_description']=temp['query_description']


   let tempArr=[]
   tempArr.push(tempVal)
   
    console.log(temp)

    temp['updatedQuery']=tempArr
  
    this.service.post('Dossier/Dossier.php?type=saveUpdatedQuery&ID='+this.updatedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        alert('Record Updated  Successfully');
        this.isView=false
        this.isNew=false
        this.View=false
        this.isUpdate=false


      } else {
        alert('Failed: An error occured, please try again!');
      }
    })

  }

  updateView(index)
  {
    this.updatedSelectedResult=this.queryUpdatedResults[index]
    console.log(this.updatedSelectedResult)
   this.View=false
   this.updatedView=true
  }

  clsoeUpdatedView()
  {
    this.View=true
    this.updatedView=false



  }

  queryClosingView(index)
  {
    this.queryClosingResults=this.results[index]
    this.queryClosing=true
    this.isUpdate=false
    this.View=false
    this.isNew=false


  }

  saveQueryClosing(data)
  {
   let  temp=data.value
    this.service.post('Dossier/Dossier.php?type=saveQueryClosing&ID='+this.queryClosingResults['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Closed  Successfully');
        this.isView=false
        this.isNew=false
        this.View=false
        this.queryClosing=false



      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }


}
