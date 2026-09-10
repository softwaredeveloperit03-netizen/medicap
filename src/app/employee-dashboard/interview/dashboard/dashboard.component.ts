import {  Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isTechnicalRound = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
      this.getCandidatesForTechnicalROund();
  }

  
  results;
  getCandidatesForTechnicalROund() {
    this.service.get('hr/candidate.php?type=getCandidatesForTechnicalROund').subscribe((response) => {
        this.results = response;
    });
  }
 
  viewResume(url) {
    url = this.service.url + '../..' + url;
    window.open(url, '_blank');
  }

 

  selectedCandidate = [];
   
  TechnicalRound(data){
    this.selectedCandidate = data;
    this.isTechnicalRound = true;
  }


  technicalRemarkData = [];



addRemark(data){
  if(!data.valid){
    alertify.error("Please Add Remark!!!!!!!!!!");
    return;
  }

  let temp = data.value;
  this.technicalRemarkData.push(temp);
  data.reset();

}







   
  saveTechnicalRoundInterView(status) {

    if (this.technicalRemarkData?.length == 0){
      alertify.error('Please Add Any Remark.....');
      return;
    }
 
    let temp = {};
    temp['candidate_id'] = this.selectedCandidate['id'];
    temp['status'] = status;
    temp['technicalRemarkData'] = this.technicalRemarkData;
 
    this.service.post('hr/candidate.php?type=saveTechnicalRoundInterView',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Response Saved Successfully');
          this.isTechnicalRound = false;
          this.getCandidatesForTechnicalROund();
          this.selectedCandidate = [];
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
