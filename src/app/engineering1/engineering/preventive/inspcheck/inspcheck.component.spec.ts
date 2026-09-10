import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InspcheckComponent } from './inspcheck.component';

describe('InspcheckComponent', () => {
  let component: InspcheckComponent;
  let fixture: ComponentFixture<InspcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InspcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InspcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
