import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TestAttComponent } from './test-att.component';

describe('TestAttComponent', () => {
  let component: TestAttComponent;
  let fixture: ComponentFixture<TestAttComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TestAttComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TestAttComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
