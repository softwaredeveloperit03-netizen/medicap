import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ManageapprovalComponent } from './manageapproval.component';

describe('ManageapprovalComponent', () => {
  let component: ManageapprovalComponent;
  let fixture: ComponentFixture<ManageapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ManageapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ManageapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
