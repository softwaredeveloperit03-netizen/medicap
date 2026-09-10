import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FesbilityFormComponent } from './fesbility-form.component';

describe('FesbilityFormComponent', () => {
  let component: FesbilityFormComponent;
  let fixture: ComponentFixture<FesbilityFormComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FesbilityFormComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FesbilityFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
