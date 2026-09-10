import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InssuranceComponent } from './inssurance.component';

describe('InssuranceComponent', () => {
  let component: InssuranceComponent;
  let fixture: ComponentFixture<InssuranceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InssuranceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InssuranceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
