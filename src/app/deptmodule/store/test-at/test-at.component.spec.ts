import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TestAtComponent } from './test-at.component';

describe('TestAtComponent', () => {
  let component: TestAtComponent;
  let fixture: ComponentFixture<TestAtComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TestAtComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TestAtComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
