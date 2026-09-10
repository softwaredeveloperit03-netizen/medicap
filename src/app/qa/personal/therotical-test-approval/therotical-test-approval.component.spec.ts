import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TheroticalTestApprovalComponent } from './therotical-test-approval.component';

describe('TheroticalTestApprovalComponent', () => {
  let component: TheroticalTestApprovalComponent;
  let fixture: ComponentFixture<TheroticalTestApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TheroticalTestApprovalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TheroticalTestApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
