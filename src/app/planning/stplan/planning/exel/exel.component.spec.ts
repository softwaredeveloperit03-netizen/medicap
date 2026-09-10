import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExelComponent } from './exel.component';

describe('ExelComponent', () => {
  let component: ExelComponent;
  let fixture: ComponentFixture<ExelComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExelComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
