import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-interviwer',
  templateUrl: './interviwer.component.html',
  styleUrls: ['./interviwer.component.css']
})
export class InterviwerComponent implements OnInit {
  isNew = false;
  
  constructor(private service:DataAccessService) {  }

  ngOnInit() {
    this.getInterviewChecklist();
    this.get_rights();
  }


  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights;
 
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')  ).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }
  
  
  results;
  getInterviewChecklist(){
    this.service.get('hr/candidate.php?type=getInterviewChecklist').subscribe(Response=>{
      this.results=Response;
    })
  }


  checklistData;
  interviewChecklistByType(checklistType){
    this.service.get('hr/candidate.php?type=interviewChecklistByType&checklistType='+checklistType).subscribe(Response=>{
      this.checklistData=Response;
    })
  }



  saveInterviewChecklist(data) {

    if (!data.valid){
      alertify.error('Please Fill All Checklist.....');
      return;
    }
 
    let temp = data.value;
  
    this.service.post('hr/candidate.php?type=saveInterviewChecklist',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Checklist Saved Successfully!!!!!!');
          this.interviewChecklistByType(temp['checklistType']);
          this.getInterviewChecklist();
          data.reset();
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }















  
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }



  
 

   
    

}
