import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approve-formula-aand-generate-material-code',
  templateUrl: './approve-formula-aand-generate-material-code.component.html',
  styleUrls: ['./approve-formula-aand-generate-material-code.component.css']
})
export class ApproveFormulaAAndGenerateMaterialCodeComponent implements OnInit {

  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getTetetiveFormulaForCodeCreation();
  }
  
 
  getTetetiveFormulaForCodeCreation() {
    this.service.get('npd/npd.php?type=getTetetiveFormulaForCodeCreation').subscribe(response => {
      this.leadResults = response;
    });
  }
  

  isView = false;

  selectedFormula = [];
  selectedMaterial = [];
  view(data){
    this.selectedFormula = data;
    this.selectedMaterial = this.selectedFormula['materialList']
    this.isView = true;
    this.isCodeGenerated = false;
  }

 
 
  loading = false;
loadingMessage = '';
isCodeGenerated = false;

createClientCode() {
  // Start loader
  this.loading = true;
  this.loadingMessage = 'Client codes are generating, please wait...';

  const startTime = Date.now();

  // Simulate processing
  this.selectedMaterial = this.selectedMaterial.map(item => {
    const paddedMatId = String(item.matId).padStart(4, '0');
    const clientCode = this.selectedFormula['regClientCode'];

    const clientMaterial_code =
      item.plant_code +
      item.materialTypeCode +
      clientCode +
      item.materialSubTypeCode +
      paddedMatId;

    return { ...item, clientMaterial_code };
  });

  // Ensure loader stays visible at least 5 seconds
  const elapsed = Date.now() - startTime;
  const remainingTime = Math.max(0, 5000 - elapsed);

  setTimeout(() => {
    this.loading = false;
    this.loadingMessage = '';
  }, remainingTime);

  this.isCodeGenerated = true;
}


 

  saveGeneratedCOde() {

    // Check if all materials have a valid clientMaterial_code
    const allCodesGenerated = this.selectedMaterial.every(
      item => item.clientMaterial_code && item.clientMaterial_code.trim() !== ""
    );

    if (!allCodesGenerated) {
      alertify.error('Please generate all client material codes before saving!');
      return;
    }
  
    let temp = {};
    temp['selectedMaterial'] = this.selectedMaterial;
    temp['selectedFormula'] = this.selectedFormula;
  
    this.service.post('npd/npd.php?type=saveGeneratedCOde', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Client Material Code Saved successfully!');
        this.isView =false;
        this.isCodeGenerated =false;
        this.getTetetiveFormulaForCodeCreation();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.leadResults; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.leadResults.filter((material) => {
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


