import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PrevenapprovalComponent } from './prevenapproval.component';

describe('PrevenapprovalComponent', () => {
  let component: PrevenapprovalComponent;
  let fixture: ComponentFixture<PrevenapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PrevenapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PrevenapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
