import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SoftwarequestionnaireComponent } from './softwarequestionnaire.component';

describe('SoftwarequestionnaireComponent', () => {
  let component: SoftwarequestionnaireComponent;
  let fixture: ComponentFixture<SoftwarequestionnaireComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SoftwarequestionnaireComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SoftwarequestionnaireComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
