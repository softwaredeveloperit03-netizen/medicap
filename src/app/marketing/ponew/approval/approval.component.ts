import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';



@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
 
 
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPOs(); 
  }

  pendingpo;
  getPendingPOs(){
    this.service.get('marketing/po.php?type=getPendingPOs').subscribe(response =>{
      this.pendingpo =response;
    });
  }

 
  isView = false;
  selectedResult = [];

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }
    
  updatePendingPOs(status,id) {

    this.service.get('marketing/po.php?type=updatePendingPOs&status=' + status + '&id=' + id).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
           alert('Plan Approved Successfully');
           this.isView = false;
           this.getPendingPOs();
       } else {
        alert('An error has occurred, please try again');
      }

    });
  }

 
  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
  }



  
   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.pendingpo; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.pendingpo.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
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


