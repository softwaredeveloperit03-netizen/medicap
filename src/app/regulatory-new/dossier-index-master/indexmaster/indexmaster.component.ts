import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-indexmaster',
  templateUrl: './indexmaster.component.html',
  styleUrls: ['./indexmaster.component.css']
})
export class IndexmasterComponent implements OnInit {



  indexmasterlist=[]
  insertType:any
  indexmasterChecklist=[]
document: any;
  selectedFile: any;
  isView=false
    Results: any
    uploadData: FormData;
    structureFile: File
    finalResult: any;

  constructor(private service: DataAccessService, private router: Router){ }

  ngOnInit(): void {
    this.getIndexMasterData()
  }

  addData(data){
   let  temp=data.value
   this.indexmasterlist.push(temp)
   console.log(this.indexmasterlist)


  }


Add(data){
  let temp=data.value
  if(this.insertType=='Upload')
    {
  temp['file']=this.document
    }
  this.indexmasterChecklist.push(temp)
  console.log(this.indexmasterChecklist)

}



  saveChecklist(){
    let temp={}
    temp['indexMasterList1']=this.indexmasterlist;
    temp['indexMasterList2']=this.indexmasterChecklist;
    temp["file"]=this.document
    this.service.post('Dossier/Dossier.php?type=saveIndexMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
         alert('Record Inserted Successfully');
            this.router.navigate(['/regulatory-new/dossierIndex']);

      } else {
        alert('Failed: An error occured, please try again!');
      }
    })

  }
  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.structureFile = event.target.files[0];

}

 this.uploadData = new FormData();

if (this.structureFile !== undefined) {
  this.uploadData.append('structure_file', this.structureFile, this.structureFile.name);
  this.document= this.structureFile.name



}

this.service.post('Dossier/Dossier.php?type=saveDossierFile' ,this.uploadData).subscribe(response => {
  if (response['status'] == 'success') {
    alertify.success(' file uploaded  successfully');

  } else {
    alertify.error('Failed: An error occured, please try again!');
  }
});

  }



  getIndexMasterData()
  {
    this.service.get('Dossier/Dossier.php?type=getIndexMasterLog').subscribe(response=>{
      this.Results = response;
      console.log(this.finalResult)
        
    })
  }

  
 


}
