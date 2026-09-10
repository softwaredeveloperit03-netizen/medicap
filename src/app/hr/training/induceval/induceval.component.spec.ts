import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InducevalComponent } from './induceval.component';

describe('InducevalComponent', () => {
  let component: InducevalComponent;
  let fixture: ComponentFixture<InducevalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InducevalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InducevalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
