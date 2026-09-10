import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinialsopComponent } from './finialsop.component';

describe('FinialsopComponent', () => {
  let component: FinialsopComponent;
  let fixture: ComponentFixture<FinialsopComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinialsopComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinialsopComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
