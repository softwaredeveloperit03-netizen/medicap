import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FgProductApprovalComponent } from './fg-product-approval.component';

describe('FgProductApprovalComponent', () => {
  let component: FgProductApprovalComponent;
  let fixture: ComponentFixture<FgProductApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FgProductApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FgProductApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
