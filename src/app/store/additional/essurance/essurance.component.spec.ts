import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EssuranceComponent } from './essurance.component';

describe('EssuranceComponent', () => {
  let component: EssuranceComponent;
  let fixture: ComponentFixture<EssuranceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EssuranceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EssuranceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
