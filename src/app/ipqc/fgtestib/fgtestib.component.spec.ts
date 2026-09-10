import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FgtestibComponent } from './fgtestib.component';

describe('FgtestibComponent', () => {
  let component: FgtestibComponent;
  let fixture: ComponentFixture<FgtestibComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FgtestibComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FgtestibComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
