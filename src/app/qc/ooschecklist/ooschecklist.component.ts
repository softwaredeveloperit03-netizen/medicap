import { Component, OnInit } from '@angular/core';
 import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ooschecklist',
  templateUrl: './ooschecklist.component.html',
  styleUrls: ['./ooschecklist.component.css']
})
export class OoschecklistComponent implements OnInit {

  Result='NA';
Comments='NA';
point=0;
subpoint=0;

  checklistList = [];
  evaluation_parameter;
  checklist_heading ='';
  ooschecklist_data;
  check_index;
  
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
   this.getooschecklist();
   }
   checklistListfinal;
   getooschecklist() {
    this.ooschecklist_data =[];
    this.service.get('qc/testing/checklist.php?type=getooschecklistmaster').subscribe(response => {
      this.checklistListfinal = response;
     });
  }
 

 
    addData(data: any) {
      if (!data.valid) {
        alert('All fields are required');
        return;
      }
    
      // Extract the data value
      let temp = data.value;
    
      // Check if Comments is an array and transform it into key-value pairs
      if (Array.isArray(temp.Comments)) {
        temp.Comments = temp.Comments.map(comment => ({
          cmt_parameter: comment
        }));
      }
    
      // Add the transformed data to checklistList
      this.checklistList.push(temp);
    
      console.log(this.checklistList);
    
      // Reset the form
      data.resetForm();
      this.point=0;
      this.subpoint=0;
    }
 
  delCehk(index){
    this.checklistList.splice(index, 1);

  }

  saveChecklist() {
   
    let temp ={}
    temp['checklistList']=this.checklistList;

    this.service.post('qc/testing/checklist.php?type=saveOOSChecklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.checklistList = [];
        this.checklist_heading ='';
        this.getooschecklist();
 
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  delData(index) {
    this.checklistList.splice(index, 1);
  }
 

}
