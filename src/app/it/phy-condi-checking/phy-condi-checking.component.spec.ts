import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PhyCondiCheckingComponent } from './phy-condi-checking.component';

describe('PhyCondiCheckingComponent', () => {
  let component: PhyCondiCheckingComponent;
  let fixture: ComponentFixture<PhyCondiCheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PhyCondiCheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PhyCondiCheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
