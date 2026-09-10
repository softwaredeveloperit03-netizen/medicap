import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChemberComponent } from './chember.component';

describe('ChemberComponent', () => {
  let component: ChemberComponent;
  let fixture: ComponentFixture<ChemberComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChemberComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChemberComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
